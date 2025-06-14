<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\OpenAIService;
use App\Models\Todo;
use Illuminate\Support\Facades\Http;



class TodoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //
        return response()->json($request->user()->todos);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
         $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $todo = $request->user()->todos()->create($data);
        return response()->json($todo, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request,string $id)
    {
        $todo = $request->user()->todos()->findOrFail($id);
        return response()->json($todo);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $todo = $request->user()->todos()->findOrFail($id);

        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'completed' => 'boolean',
        ]);

        $todo->update($data);
        return response()->json($todo);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request,string $id)
    {
        $todo = $request->user()->todos()->findOrFail($id);
        $todo->delete();
        return response()->json(['message' => 'Deleted']);
    }


    public function summarize($id, OpenAIService $openAIService)
    {
        $todoText = "Review the project plan, prepare a presentation in PowerPoint, draft agenda points, and send calendar invites to the team.";
         $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
            'OpenAI-Beta' => 'assistants=v2',
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a helpful assistant that summarizes todo items and adds meaningful objectives to them.',
                ],
                [
                    'role' => 'user',
                    'content' => "Summarize and suggest a clear objective for: {$todoText}",
                ],
            ],
            'temperature' => 0.7,
        ]);

        return $response->json();

        $todo = Todo::findOrFail($id);

         if (!$todo) {
        return response()->json(['error' => 'Todo not found'], 404);
    }
        $summary = $openAIService->summarizeTodo([
            'title' => $todo->title,
            'description' => $todo->description,
        ]);

        return response()->json([
            'summary' => $summary,
        ]);
    }
}
