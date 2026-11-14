<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Google\Client;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GlobalFunction extends Model
{
    use HasFactory;

    public static function sendSimpleResponse($status, $msg)
    {
        return response()->json(['status' => $status, 'message' => $msg]);
    }
    
    public static function sendDataResponse($status, $msg, $data)
    {
        return response()->json(['status' => $status, 'message' => $msg, 'data' => $data]);
    }

    public static function sendPushNotificationToAllUsers($title, $message)
    {
        $credentialsPath = base_path('googleCredentials.json');
        if (!File::exists($credentialsPath)) {
            Log::error('FCM Error: googleCredentials.json file not found.');
            return json_encode(['status' => false, 'message' => 'googleCredentials.json file not found on server.']);
        }

        $contents = File::get($credentialsPath);
        $json = json_decode($contents, true);
        if (!$json || empty($json['project_id'])) {
            Log::error('FCM Error: googleCredentials.json is invalid or missing project_id.');
            return json_encode(['status' => false, 'message' => 'Invalid googleCredentials.json on server.']);
        }

        try {
            $client = new Client();
            $client->setAuthConfig($credentialsPath);
            $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
            $tokenData = $client->fetchAccessTokenWithAssertion();
            $accessToken = $tokenData['access_token'] ?? ($client->getAccessToken()['access_token'] ?? null);
        } catch (\Throwable $e) {
            Log::error('FCM Auth Error: ' . $e->getMessage());
            return json_encode(['status' => false, 'message' => 'FCM authentication failed: ' . $e->getMessage()]);
        }

        if (empty($accessToken)) {
            Log::error('FCM Error: Empty access token.');
            return json_encode(['status' => false, 'message' => 'Failed to obtain FCM access token.']);
        }

        $topicPrefix = env('NOTIFICATION_TOPIC', 'orange');
        if (empty($topicPrefix)) {
            $topicPrefix = 'orange';
        }

        $url = 'https://fcm.googleapis.com/v1/projects/' . $json['project_id'] . '/messages:send';
        $notificationArray = array('title' => (string) $title, 'body' => (string) $message);

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken
        ];

        // Construct message for iOS
        $fields_ios = array(
            'message' => [
                'topic' => $topicPrefix . '_ios',
                'data' => $notificationArray,
                'notification' => $notificationArray,
                'apns' => [
                    'payload' => [
                        'aps' => ['sound' => 'default']
                    ]
                ]
            ],
        );

        $chIos = curl_init();
        curl_setopt($chIos, CURLOPT_URL, $url);
        curl_setopt($chIos, CURLOPT_POST, true);
        curl_setopt($chIos, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($chIos, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($chIos, CURLOPT_POSTFIELDS, json_encode($fields_ios));
        $resultIos = curl_exec($chIos);
        $httpCodeIos = curl_getinfo($chIos, CURLINFO_HTTP_CODE);
        $curlErrIos = curl_error($chIos);
        curl_close($chIos);

        // Construct message for Android (include notification + high priority for background/killed delivery + data for foreground handler)
        $fields_android = array(
            'message' => [
                'topic' => $topicPrefix . '_android',
                'notification' => $notificationArray,
                'data' => $notificationArray,
                'android' => [
                    'priority' => 'HIGH',
                    'notification' => [
                        'sound' => 'default',
                    ],
                ],
            ],
        );

        $chAndroid = curl_init();
        curl_setopt($chAndroid, CURLOPT_URL, $url);
        curl_setopt($chAndroid, CURLOPT_POST, true);
        curl_setopt($chAndroid, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($chAndroid, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($chAndroid, CURLOPT_POSTFIELDS, json_encode($fields_android));
        $resultAndroid = curl_exec($chAndroid);
        $httpCodeAndroid = curl_getinfo($chAndroid, CURLINFO_HTTP_CODE);
        $curlErrAndroid = curl_error($chAndroid);
        curl_close($chAndroid);

        if ($httpCodeIos < 200 || $httpCodeIos >= 300) {
            Log::error('FCM iOS Send Error (' . $httpCodeIos . '): ' . ($curlErrIos ?: $resultIos));
        }
        if ($httpCodeAndroid < 200 || $httpCodeAndroid >= 300) {
            Log::error('FCM Android Send Error (' . $httpCodeAndroid . '): ' . ($curlErrAndroid ?: $resultAndroid));
        }

        if (($httpCodeAndroid >= 200 && $httpCodeAndroid < 300) || ($httpCodeIos >= 200 && $httpCodeIos < 300)) {
            return json_encode([
                'status' => true,
                'message' => 'Notification sent successfully',
                'topic_prefix' => $topicPrefix,
                'android' => json_decode($resultAndroid, true),
                'ios' => json_decode($resultIos, true),
            ]);
        }

        return json_encode([
            'status' => false,
            'message' => 'FCM rejected notification (Android HTTP ' . $httpCodeAndroid . ', iOS HTTP ' . $httpCodeIos . '): ' . ($curlErrAndroid ?: $resultAndroid),
        ]);
    }

    public static function createMediaUrl($media)
    {
        $url = url('public/storage/' . $media);
        return $url;
    }

    public static function uploadFilToS3($request, $key)
    {
        $s3 = Storage::disk('s3');
        $file = $request->file($key);
        $fileName = time() . $file->getClientOriginalName();
        $fileName = str_replace(" ", "_", $fileName);
        $filePath = 'uploads/' . $fileName;
        $result =  $s3->put($filePath, file_get_contents($file), 'public-read');
        return $filePath;
    }

    public static function point2point_distance($lat1, $lon1, $lat2, $lon2, $unit = 'K', $radius)
    {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        $unit = strtoupper($unit);

        if ($unit == "K") {
            return (($miles * 1.609344) <= $radius);
        } else if ($unit == "N") {
            return ($miles * 0.8684);
        } else {
            return $miles;
        }
    }

    public static function cleanString($string)
    {
        return  str_replace(array('<', '>', '{', '}', '[', ']', '`'), '', $string);
    }

    public static function deleteFile($filename)
    {
        if ($filename != null && file_exists(storage_path('app/public/' . $filename))) {
            unlink(storage_path('app/public/' . $filename));
        }
    }

    public static function saveFileAndGivePath($file)
    {
        if ($file != null) {
            $path = $file->store('uploads', 'public');
            return $path;
        } else {
            return null;
        }
    }
}
