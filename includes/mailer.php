<?php

require_once __DIR__ . '/bootstrap.php';

function mailer_send_resend($to, $subject, $html)
{
    $config = tastemap_config();
    $apiKey = isset($config['resend_api_key']) ? $config['resend_api_key'] : '';
    $from = isset($config['mail_from']) ? $config['mail_from'] : '';

    if (!tastemap_has_real_key($apiKey) || !$from) {
        return [
            'ok' => false,
            'error' => 'Resend 메일 API 설정이 필요합니다.',
        ];
    }

    $payload = json_encode([
        'from' => $from,
        'to' => [$to],
        'subject' => $subject,
        'html' => $html,
    ], JSON_UNESCAPED_UNICODE);

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Authorization: Bearer {$apiKey}\r\nContent-Type: application/json\r\n",
            'content' => $payload,
            'ignore_errors' => true,
            'timeout' => 8,
        ],
    ]);

    $response = @file_get_contents('https://api.resend.com/emails', false, $context);
    $statusLine = isset($http_response_header[0]) ? $http_response_header[0] : '';
    $isOk = strpos($statusLine, ' 200 ') !== false || strpos($statusLine, ' 201 ') !== false;

    if (!$isOk) {
        return [
            'ok' => false,
            'error' => $response ?: '메일 발송 요청에 실패했습니다.',
        ];
    }

    $data = json_decode($response, true);
    return [
        'ok' => true,
        'id' => isset($data['id']) ? $data['id'] : null,
    ];
}

function mailer_verification_html($nickname, $verificationUrl)
{
    $safeNickname = tastemap_h($nickname);
    $safeUrl = tastemap_h($verificationUrl);

    return '
        <div style="font-family: Inter, Arial, sans-serif; color: #171717; line-height: 1.6;">
            <h1 style="font-size: 24px;">우리들의 맛집 지도 이메일 인증</h1>
            <p>' . $safeNickname . '님, 아래 버튼을 눌러 이메일 인증을 완료하세요.</p>
            <p>
                <a href="' . $safeUrl . '" style="display:inline-block;background:#000;color:#fff;text-decoration:none;padding:12px 18px;border-radius:8px;">
                    이메일 인증하기
                </a>
            </p>
            <p style="color:#60646c;font-size:14px;">버튼이 열리지 않으면 아래 주소를 브라우저에 붙여넣으세요.</p>
            <p style="color:#0d74ce;font-size:14px;">' . $safeUrl . '</p>
        </div>
    ';
}

