<?php
/**
 * ID Verification Endpoint
 * Supports automatic 0, 90, 180, and 270 degree rotation.
 * Compares detected ID portraits against the user's selfie.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

function respond($payload, $httpStatus = 200)
{
    http_response_code($httpStatus);
    echo json_encode($payload);
    exit;
}

// =====================================================
// 1. IMAGE VALIDATION
// =====================================================

function decodeImageInput($input, $label)
{
    if (!is_string($input) || trim($input) === '') {
        return ['error' => $label . ' image is missing.'];
    }

    $base64 = preg_replace(
        '#^data:image/[a-zA-Z0-9.+-]+;base64,#',
        '',
        trim($input)
    );

    $binary = base64_decode($base64, true);

    if ($binary === false || $binary === '') {
        return ['error' => 'Please upload a valid ' . $label . ' image.'];
    }

    if (strlen($binary) > 12 * 1024 * 1024) {
        return ['error' => $label . ' image is too large.'];
    }

    $info = @getimagesizefromstring($binary);

    if ($info === false) {
        return ['error' => 'The ' . $label . ' upload is not a readable image.'];
    }

    $allowedMimes = [
        'image/jpeg',
        'image/png',
        'image/webp'
    ];

    if (!in_array($info['mime'], $allowedMimes, true)) {
        return ['error' => 'Please upload JPG, PNG, or WebP images.'];
    }

    $width = (int) $info[0];
    $height = (int) $info[1];

    if ($width < 100 || $height < 100) {
        return ['error' => $label . ' image is too small.'];
    }

    if ($width > 8000 || $height > 8000) {
        return ['error' => $label . ' image dimensions are too large.'];
    }

    $image = @imagecreatefromstring($binary);

    if (!$image) {
        return ['error' => 'Unable to process the ' . $label . ' image.'];
    }

    return [
        'binary' => $binary,
        'image' => $image,
        'width' => imagesx($image),
        'height' => imagesy($image),
        'mime' => $info['mime']
    ];
}

// =====================================================
// 2. IMAGE CONVERSION
// =====================================================

function imageResourceToJpegBase64($image, $quality = 90)
{
    ob_start();
    imagejpeg($image, null, $quality);
    $jpeg = ob_get_clean();

    if (!$jpeg) {
        return false;
    }

    return base64_encode($jpeg);
}

// =====================================================
// 3. FACE++ API
// =====================================================

function callFacePlusPlus($endpoint, $postData)
{
    global $FACE_API_KEY, $FACE_API_SECRET;

    $url = 'https://api-us.faceplusplus.com/facepp/v3/' . $endpoint;

    $postData['api_key'] = $FACE_API_KEY;
    $postData['api_secret'] = $FACE_API_SECRET;

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => http_build_query($postData),
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 35
    ]);

    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

    curl_close($ch);

    if ($response === false || $curlError !== '') {
        return [
            0,
            $curlError ?: 'Unknown cURL error'
        ];
    }

    return [$httpCode, $response];
}

function facePlusPlusError($response, $httpCode, $operation)
{
    if ($httpCode === 0) {
        return $operation . ' request failed: ' . $response;
    }

    $decoded = json_decode($response, true);

    if (!empty($decoded['error_message'])) {
        return $decoded['error_message'];
    }

    return 'Face++ ' . $operation . ' API error (HTTP ' . $httpCode . ').';
}

// =====================================================
// 4. AUTOMATIC ROTATION AND MULTI-SCALE FACE DETECTION
// =====================================================

function detectIdFaces($idImageBinary, $originalWidth, $originalHeight)
{
    $rotationAngles = [0, 90, 180, 270];

    $scales = [1.0, 1.5, 2.0, 3.0];

    $maxDimension = 3000;

    foreach ($rotationAngles as $angle) {

        $source = @imagecreatefromstring($idImageBinary);

        if (!$source) {
            return ['error' => 'The ID image could not be processed.'];
        }

        // Automatically rotate the image.
        // GD uses counter-clockwise rotation.

        if ($angle !== 0) {

            $rotated = imagerotate($source, 360 - $angle, 0);

            imagedestroy($source);

            if (!$rotated) {
                continue;
            }

            $source = $rotated;
        }

        $workingWidth = imagesx($source);
        $workingHeight = imagesy($source);

        ob_start();
        imagejpeg($source, null, 92);
        $workingJpeg = ob_get_clean();

        imagedestroy($source);

        if (!$workingJpeg) {
            continue;
        }

        // Test different image sizes.

        foreach ($scales as $scale) {

            $newWidth = (int) round($workingWidth * $scale);
            $newHeight = (int) round($workingHeight * $scale);

            if (
                $newWidth > $maxDimension ||
                $newHeight > $maxDimension
            ) {
                continue;
            }

            if ($scale === 1.0) {

                $imageToSend = base64_encode($workingJpeg);

            } else {

                $baseImage = @imagecreatefromstring($workingJpeg);

                if (!$baseImage) {
                    continue;
                }

                $resized = imagecreatetruecolor(
                    $newWidth,
                    $newHeight
                );

                imagecopyresampled(
                    $resized,
                    $baseImage,
                    0, 0, 0, 0,
                    $newWidth,
                    $newHeight,
                    $workingWidth,
                    $workingHeight
                );

                imagedestroy($baseImage);

                $imageToSend = imageResourceToJpegBase64(
                    $resized,
                    90
                );

                imagedestroy($resized);

                if ($imageToSend === false) {
                    continue;
                }
            }

            // Send image to Face++.

            list($httpCode, $response) = callFacePlusPlus(
                'detect',
                [
                    'image_base64' => $imageToSend,
                    'return_landmark' => 0,
                    'return_attributes' => 'none',
                    'min_face_size' => 15
                ]
            );

            if ($httpCode !== 200) {
                return [
                    'error' => facePlusPlusError(
                        $response,
                        $httpCode,
                        'Detect'
                    )
                ];
            }

            $result = json_decode($response, true);

            $detectedFaces = isset($result['faces']) &&
                is_array($result['faces'])
                ? $result['faces']
                : [];

            if (count($detectedFaces) === 0) {
                continue;
            }

            // Convert face coordinates to rotated original image coordinates.

            $rectangles = [];

            $scaleX = $workingWidth / $newWidth;
            $scaleY = $workingHeight / $newHeight;

            foreach ($detectedFaces as $face) {

                if (empty($face['face_rectangle'])) {
                    continue;
                }

                $rect = $face['face_rectangle'];

                $left = max(
                    0,
                    (int) floor($rect['left'] * $scaleX)
                );

                $top = max(
                    0,
                    (int) floor($rect['top'] * $scaleY)
                );

                $right = min(
                    $workingWidth,
                    (int) ceil(
                        ($rect['left'] + $rect['width']) * $scaleX
                    )
                );

                $bottom = min(
                    $workingHeight,
                    (int) ceil(
                        ($rect['top'] + $rect['height']) * $scaleY
                    )
                );

                $faceWidth = $right - $left;
                $faceHeight = $bottom - $top;

                if ($faceWidth > 0 && $faceHeight > 0) {

                    $rectangles[] = [
                        'left' => $left,
                        'top' => $top,
                        'width' => $faceWidth,
                        'height' => $faceHeight
                    ];
                }
            }

            if (count($rectangles) > 0) {

                return [
                    'faces' => $rectangles,
                    'working_binary' => $workingJpeg,
                    'rotation_degrees' => $angle
                ];
            }
        }
    }

    return [
        'error' => 'No face was detected on the ID after checking all four image orientations. Please upload a clear, well-lit photo showing the ID portrait.'
    ];
}

// =====================================================
// 5. CROP DETECTED ID FACE
// =====================================================

function cropIdFaceToBase64($idImageBinary, $rect)
{
    $image = @imagecreatefromstring($idImageBinary);

    if (!$image) {
        return false;
    }

    $imageWidth = imagesx($image);
    $imageHeight = imagesy($image);

    $padX = (int) round($rect['width'] * 0.45);
    $padY = (int) round($rect['height'] * 0.45);

    $cropX = max(0, $rect['left'] - $padX);
    $cropY = max(0, $rect['top'] - $padY);

    $cropRight = min(
        $imageWidth,
        $rect['left'] + $rect['width'] + $padX
    );

    $cropBottom = min(
        $imageHeight,
        $rect['top'] + $rect['height'] + $padY
    );

    $cropWidth = $cropRight - $cropX;
    $cropHeight = $cropBottom - $cropY;

    if ($cropWidth < 1 || $cropHeight < 1) {
        imagedestroy($image);
        return false;
    }

    $cropped = imagecrop(
        $image,
        [
            'x' => $cropX,
            'y' => $cropY,
            'width' => $cropWidth,
            'height' => $cropHeight
        ]
    );

    imagedestroy($image);

    if (!$cropped) {
        return false;
    }

    // Resize only if the image is excessively large.

    $longestSide = max(
        imagesx($cropped),
        imagesy($cropped)
    );

    if ($longestSide > 900) {

        $ratio = 900 / $longestSide;

        $newWidth = max(
            1,
            (int) round(imagesx($cropped) * $ratio)
        );

        $newHeight = max(
            1,
            (int) round(imagesy($cropped) * $ratio)
        );

        $resized = imagecreatetruecolor(
            $newWidth,
            $newHeight
        );

        imagecopyresampled(
            $resized,
            $cropped,
            0, 0, 0, 0,
            $newWidth,
            $newHeight,
            imagesx($cropped),
            imagesy($cropped)
        );

        imagedestroy($cropped);

        $cropped = $resized;
    }

    $base64 = imageResourceToJpegBase64(
        $cropped,
        92
    );

    imagedestroy($cropped);

    return $base64;
}

// =====================================================
// 6. AUTHENTICATION
// =====================================================

if (!isset($_SESSION['user_id'])) {
    respond([
        'success' => false,
        'error' => 'Unauthorized. Please log in again.'
    ], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond([
        'success' => false,
        'error' => 'POST method required.'
    ], 405);
}

if (
    !isset($_POST['id_image_base64']) ||
    !isset($_POST['selfie_base64'])
) {
    respond([
        'success' => false,
        'error' => 'Missing ID image or selfie image.'
    ], 400);
}

// =====================================================
// 7. FACE++ CREDENTIALS
// =====================================================

$FACE_API_KEY = defined('FACE_API_KEY')
    ? FACE_API_KEY
    : getenv('FACE_API_KEY');

$FACE_API_SECRET = defined('FACE_API_SECRET')
    ? FACE_API_SECRET
    : getenv('FACE_API_SECRET');

if (
    empty($FACE_API_KEY) ||
    empty($FACE_API_SECRET)
) {
    respond([
        'success' => false,
        'error' => 'Face verification service is not configured.'
    ], 500);
}

// =====================================================
// 8. VALIDATE ID AND SELFIE
// =====================================================

$idUpload = decodeImageInput(
    $_POST['id_image_base64'],
    'ID'
);

if (isset($idUpload['error'])) {
    respond([
        'success' => false,
        'error' => $idUpload['error']
    ], 400);
}

$selfieUpload = decodeImageInput(
    $_POST['selfie_base64'],
    'selfie'
);

if (isset($selfieUpload['error'])) {

    imagedestroy($idUpload['image']);

    respond([
        'success' => false,
        'error' => $selfieUpload['error']
    ], 400);
}

imagedestroy($idUpload['image']);
imagedestroy($selfieUpload['image']);

// =====================================================
// 9. DETECT ID PORTRAIT
// =====================================================

$detection = detectIdFaces(
    $idUpload['binary'],
    $idUpload['width'],
    $idUpload['height']
);

if (isset($detection['error'])) {

    respond([
        'success' => false,
        'error' => $detection['error']
    ]);
}

$idFaceRects = $detection['faces'];

// Use the rotated image for cropping.

$idImageForCropping = $detection['working_binary'];

if (count($idFaceRects) > 10) {

    respond([
        'success' => false,
        'error' => 'Too many faces were detected. Please upload a closer photo of the ID portrait.'
    ]);
}

// =====================================================
// 10. COMPARE ID FACES WITH SELFIE
// =====================================================

$bestConfidence = -1;
$bestFaceIndex = null;
$bestFacesInSelfie = 0;

$comparisonErrors = [];

foreach ($idFaceRects as $index => $rect) {

    $candidateFaceBase64 = cropIdFaceToBase64(
        $idImageForCropping,
        $rect
    );

    if ($candidateFaceBase64 === false) {

        $comparisonErrors[] =
            'Could not crop detected ID portrait ' . ($index + 1);

        continue;
    }

    list($httpCode, $compareResponse) = callFacePlusPlus(
        'compare',
        [
            'image_base64_1' => $candidateFaceBase64,
            'image_base64_2' => base64_encode(
                $selfieUpload['binary']
            )
        ]
    );

    if ($httpCode !== 200) {

        $comparisonErrors[] = facePlusPlusError(
            $compareResponse,
            $httpCode,
            'Compare'
        );

        continue;
    }

    $compareResult = json_decode(
        $compareResponse,
        true
    );

    if (!is_array($compareResult)) {

        $comparisonErrors[] =
            'Face++ returned an unreadable comparison response.';

        continue;
    }

    $facesInSelfie = isset($compareResult['faces2']) &&
        is_array($compareResult['faces2'])
        ? count($compareResult['faces2'])
        : 0;

    if ($facesInSelfie === 0) {

        respond([
            'success' => false,
            'error' => 'No face detected in the selfie. Please take a clear selfie.'
        ]);
    }

    if ($facesInSelfie > 1) {

        respond([
            'success' => false,
            'error' => 'Multiple faces detected in the selfie. Please take a photo with only one face.'
        ]);
    }

    if (isset($compareResult['confidence'])) {

        $confidence = (float) $compareResult['confidence'];

        if ($confidence > $bestConfidence) {

            $bestConfidence = $confidence;
            $bestFaceIndex = $index;
            $bestFacesInSelfie = $facesInSelfie;
        }

    } elseif (!empty($compareResult['error_message'])) {

        $comparisonErrors[] =
            $compareResult['error_message'];
    }
}

// =====================================================
// 11. FINAL VERIFICATION RESULT
// =====================================================

if ($bestConfidence < 0) {

    $message = !empty($comparisonErrors)
        ? 'Unable to compare the ID portrait with the selfie. ' .
            $comparisonErrors[0]
        : 'Unable to compare the ID portrait with the selfie. Please try a clearer image.';

    respond([
        'success' => false,
        'error' => $message
    ]);
}

// Preserve original confidence threshold.

$MATCH_THRESHOLD = 65;

$isMatch = ($bestConfidence >= $MATCH_THRESHOLD);

respond([
    'success' => true,
    'is_match' => $isMatch,
    'confidence' => round($bestConfidence, 2),
    'faces_in_id' => count($idFaceRects),
    'faces_in_selfie' => $bestFacesInSelfie,
    'matched_id_face' => $bestFaceIndex + 1,
    'message' => $isMatch
        ? 'Face match successful!'
        : 'Face not matched. Please try again.'
]);

?>