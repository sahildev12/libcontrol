<?php

namespace App\Http\Controllers;

use App\Services\EmailAutomation\MarketingCampaignService;
use Illuminate\Http\Response;

class EmailUnsubscribeController extends Controller
{
    public function __invoke(string $token, MarketingCampaignService $marketing): Response
    {
        $ok = $marketing->unsubscribe($token);

        $message = $ok
            ? 'You have been unsubscribed from LibControl marketing and growth emails.'
            : 'This unsubscribe link is invalid or has already been used.';

        return response(
            '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Unsubscribe</title></head>'
            .'<body style="font-family: system-ui, sans-serif; max-width: 32rem; margin: 3rem auto; padding: 1rem;">'
            .'<h1 style="font-size: 1.25rem;">'.e($ok ? 'Unsubscribed' : 'Link not valid').'</h1>'
            .'<p>'.e($message).'</p></body></html>',
            $ok ? 200 : 404,
            ['Content-Type' => 'text/html; charset=UTF-8']
        );
    }
}
