<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_guest_conversation(): void
    {
        $response = $this->postJson('/api/conversations');

        $response->assertStatus(201)
            ->assertJsonStructure([
                'conversation' => [
                    'id',
                    'session_id',
                    'status',
                ],
            ]);

        $this->assertDatabaseHas('conversations', [
            'id' => $response->json('conversation.id'),
            'user_id' => null,
        ]);
    }

    public function test_can_list_conversations(): void
    {
        $sessionId = Conversation::generateSessionId();
        Conversation::create(['session_id' => $sessionId, 'status' => 'active']);
        Conversation::create(['session_id' => $sessionId, 'status' => 'active']);

        $response = $this->getJson('/api/conversations?session_id=' . $sessionId);

        $response->assertStatus(200)
            ->assertJsonCount(2, 'conversations');
    }

    public function test_can_show_conversation_with_messages(): void
    {
        $conversation = Conversation::create([
            'session_id' => 'test-session',
            'status' => 'active',
        ]);

        Message::createUserMessage($conversation->id, 'Hello');
        Message::createAssistantMessage($conversation->id, 'Hi there!');

        $response = $this->getJson("/api/conversations/{$conversation->id}?session_id=test-session");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'conversation' => [
                    'id',
                    'messages',
                ],
            ])
            ->assertJsonCount(2, 'conversation.messages');
    }

    public function test_can_archive_conversation(): void
    {
        $conversation = Conversation::create([
            'session_id' => 'test-session',
            'status' => 'active',
        ]);

        $response = $this->postJson("/api/conversations/{$conversation->id}/archive");

        $response->assertStatus(200);

        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'status' => 'archived',
        ]);
    }

    public function test_can_delete_conversation(): void
    {
        $conversation = Conversation::create([
            'session_id' => 'test-session',
            'status' => 'active',
        ]);

        $response = $this->deleteJson("/api/conversations/{$conversation->id}");

        $response->assertStatus(200);

        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'status' => 'deleted',
        ]);

        $this->assertSoftDeleted('conversations', [
            'id' => $conversation->id,
        ]);
    }

    public function test_conversation_generates_title_from_first_message(): void
    {
        $conversation = Conversation::create([
            'session_id' => 'test-session',
            'status' => 'active',
        ]);

        $this->assertNull($conversation->title);

        Message::createUserMessage($conversation->id, 'What is the weather today?');

        $conversation->generateTitle();
        $conversation->refresh();

        $this->assertNotNull($conversation->title);
        $this->assertStringContainsString('What is the weather', $conversation->title);
    }

    public function test_conversation_context_returns_formatted_messages(): void
    {
        $conversation = Conversation::create([
            'session_id' => 'test-session',
            'status' => 'active',
        ]);

        Message::createUserMessage($conversation->id, 'Hello');
        Message::createAssistantMessage($conversation->id, 'Hi there!');
        Message::createUserMessage($conversation->id, 'How are you?');

        $context = $conversation->getContext();

        $this->assertCount(3, $context);
        $this->assertEquals('user', $context[0]['role']);
        $this->assertEquals('Hello', $context[0]['content']);
        $this->assertEquals('model', $context[1]['role']);
        $this->assertEquals('Hi there!', $context[1]['content']);
    }

    public function test_message_creates_with_static_helpers(): void
    {
        $conversation = Conversation::create([
            'session_id' => 'test-session',
            'status' => 'active',
        ]);

        $userMessage = Message::createUserMessage($conversation->id, 'Test message');
        $this->assertEquals('user', $userMessage->role);
        $this->assertEquals('Test message', $userMessage->content);

        $assistantMessage = Message::createAssistantMessage(
            $conversation->id,
            'Response',
            100,
            500
        );
        $this->assertEquals('assistant', $assistantMessage->role);
        $this->assertEquals('Response', $assistantMessage->content);
        $this->assertEquals(100, $assistantMessage->tokens_used);
        $this->assertEquals(500, $assistantMessage->processing_time_ms);
    }

    public function test_unauthorized_access_to_conversation(): void
    {
        $conversation = Conversation::create([
            'session_id' => 'test-session',
            'status' => 'active',
        ]);

        $response = $this->getJson("/api/conversations/{$conversation->id}?session_id=wrong-session");

        $response->assertStatus(403);
    }
}

