<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use OpenAI\Laravel\Facades\OpenAI;
use App\Http\Requests\StoreChatRequest;
use Illuminate\Support\Facades\Auth;
use OpenAI\Responses\Chat\CreateResponse;

class ChatGptStoreController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(StoreChatRequest $request, string $id = null)
    {
        //Fakeando a resposta para não gastar tokens
        OpenAI::fake([
            CreateResponse::fake([
                'usage'=> ['prompt_tokens'=> 56, 'completion_tokens'=> 31, 'total_tokens'=> 87],
                'choices' => [
                    [
                        'message' => ['role' => 'assistant', 'content' => 'Boa resposta'],
                        'finish_reason' => 'stop'
                    ]
                ]
        ])]);

        $messages = [];
        if($id){
            $chat = Chat::findOrFail($id);
            $messages = $chat->context;
        }
        $messages[] = ['role' => 'user', 'content' => $request->input('prompt')];
        $response = OpenAI::chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => $messages 
        ]);
        $messages[] = ['role' => 'assistant', 'content' => $response->choices[0]->message->content];
        $chat = Chat::updateOrCreate([
            'id' => $id,
            'user_id' => Auth::id()
        ],[
            'context' => $messages
        ]);

        return redirect()->route('chat.show', [$chat->id]);
    }
}
