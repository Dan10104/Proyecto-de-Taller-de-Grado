<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChatbotRequest;
use App\Services\Chatbot\ChatbotService;
use App\Services\Chatbot\ChatbotSettings;
use Inertia\Inertia;

class ChatbotController extends Controller
{
    public function index(ChatbotSettings $settings)
    {
        return Inertia::render('Asistente/Index', ['maxInputChars' => $settings->integer('max_input_chars')]);
    }

    public function consult(ChatbotRequest $request, ChatbotService $service)
    {
        return response()->json($service->consult($request, $request->validated('pregunta')));
    }
}
