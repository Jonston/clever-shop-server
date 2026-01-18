# Chat System with AI Assistant - Usage Guide

## Overview

This chat system provides a complete AI assistant powered by Gemini 2.0 Flash with support for:
- Conversation history and context
- Guest sessions (anonymous users)
- Parallel function calling
- Real-time WebSocket notifications
- Detailed logging and metrics

## API Endpoints

### Chat Endpoints

#### Send Message
```http
POST /api/chat/message
Content-Type: application/json

{
  "prompt": "Show me all phones",
  "conversation_id": 1,  // optional
  "session_id": "uuid"   // optional, for guests
}
```

Response:
```json
{
  "conversation_id": 1,
  "session_id": "uuid-here",
  "message": "I found 3 phones...",
  "message_id": 5,
  "processing_time_ms": 1234,
  "iterations": 2
}
```

### Conversation Management

#### List Conversations
```http
GET /api/conversations?session_id=uuid-here
```

#### Create Conversation
```http
POST /api/conversations
Content-Type: application/json

{
  "title": "Optional title"
}
```

#### Get Conversation with Messages
```http
GET /api/conversations/1?session_id=uuid-here
```

#### Archive Conversation
```http
POST /api/conversations/1/archive
```

#### Delete Conversation
```http
DELETE /api/conversations/1
```

## Usage Examples

### Guest User Flow

1. **Start a new conversation** (no auth required):
```bash
curl -X POST http://localhost/api/chat/message \
  -H "Content-Type: application/json" \
  -d '{
    "prompt": "List all products in the phones category"
  }'
```

Response will include a `session_id` that you should save for subsequent requests.

2. **Continue the conversation**:
```bash
curl -X POST http://localhost/api/chat/message \
  -H "Content-Type: application/json" \
  -d '{
    "prompt": "Show me the cheapest one",
    "conversation_id": 1,
    "session_id": "your-session-id"
  }'
```

### Authenticated User Flow

When authenticated, the system automatically associates conversations with the user:

```bash
curl -X POST http://localhost/api/chat/message \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer your-token" \
  -d '{
    "prompt": "Create a new product named iPhone 16",
    "conversation_id": 1
  }'
```

## WebSocket Events

Subscribe to conversation-specific channels to receive real-time updates:

```javascript
// Subscribe to conversation events
Echo.channel('conversation.1')
  .listen('.assistant.processing', (e) => {
    console.log('Processing:', e.action, e.message);
  })
  .listen('.iteration.complete', (e) => {
    console.log(`Iteration ${e.iteration}: ${e.function_name} (${e.time_ms}ms)`);
  })
  .listen('.message.complete', (e) => {
    console.log('Message:', e.message);
  });
```

## Parallel Function Calling

The system supports parallel execution of independent functions:

**Example: Get product AND list categories simultaneously**
```
User: "Show me product 5 and list all categories"

Iteration 1:
  - get_product(id=5)        } Executed in parallel
  - list_categories()        }
  
Final: "Here's product 5: ... And here are the categories: ..."
```

**Example: Sequential dependent tasks**
```
User: "Apply 20% discount to all phones"

Iteration 1: list_products(category="phones") → 3 products
Iteration 2: update_product(id=1, price=799.2)
Iteration 3: update_product(id=2, price=719.2)
Iteration 4: update_product(id=3, price=559.2)

Final: "I've applied 20% discount to 3 phones."
```

## Database Schema

### conversations
- `id` - Primary key
- `user_id` - Foreign key to users (nullable for guests)
- `session_id` - UUID for guest sessions (nullable for auth users)
- `title` - Auto-generated from first message
- `status` - active/archived/deleted
- `metadata` - JSON for additional data
- `last_message_at` - Timestamp of last message

### messages
- `id` - Primary key
- `conversation_id` - Foreign key to conversations
- `role` - user/assistant/system/function
- `content` - Message text
- `metadata` - JSON for additional data
- `parent_message_id` - For threading (nullable)
- `tokens_used` - Token count from AI
- `processing_time_ms` - Processing time

### function_executions
- `id` - Primary key
- `message_id` - Foreign key to messages
- `function_name` - Name of executed function
- `arguments` - JSON function arguments
- `result` - JSON function result
- `execution_time_ms` - Execution time
- `status` - pending/success/failed
- `error_message` - Error details (nullable)

## Available AI Functions

1. **list_products** - List all products, optionally filter by category
2. **get_product** - Get details of a specific product by ID
3. **create_product** - Create a new product
4. **update_product** - Update an existing product
5. **delete_product** - Delete a product by ID

## Features

### Context Management
- Automatically loads last 20 messages as context
- Converts to Gemini format (user/model roles)
- Maintains conversation flow

### Guest Sessions
- Automatic session ID generation
- Session-based access control
- Can convert to user account later

### Metrics & Logging
- Processing time tracking
- Token usage recording
- Function execution logs
- Iteration count
- Error tracking

### Security
- Access validation for conversations
- Session-based guest isolation
- User-based conversation ownership
- Soft delete support

## Backward Compatibility

The original `/api/assistant` endpoint is still available for backward compatibility:

```bash
curl -X POST http://localhost/api/assistant \
  -H "Content-Type: application/json" \
  -d '{"prompt": "List products"}'
```

This uses the legacy AssistantService without conversation support.
