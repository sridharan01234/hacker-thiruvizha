# Medical Chat Assistant - Backend

A Laravel-based backend API for the Medical Chat Assistant application with authentication and chat functionality.

## Requirements

- PHP >= 8.1  
- Composer  
- MySQL/PostgreSQL  
- Laravel 10.x  

## Installation

### 1. Clone the repository
```bash
git clone <repository-url>
cd medical-chat-backend
```

### 2. Install dependencies
```bash
composer install
```

### 3. Configure environment variables
```bash
cp .env.example .env
```
Modify your `.env` file with:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password

LOCAL_AI_ENDPOINT=http://localhost:1234/v1/chat/completions
AI_TEMPERATURE=0.5
AI_MAX_TOKENS=100
AI_TOP_P=1
AI_FREQUENCY_PENALTY=0
AI_PRESENCE_PENALTY=0
AI_MODEL_NAME=default-model

SANCTUM_STATEFUL_DOMAINS=localhost:3000
SESSION_DOMAIN=localhost
CORS_ALLOWED_ORIGINS=http://localhost:3000
```

### 4. Generate application key
```bash
php artisan key:generate
```

### 5. Run migrations
```bash
php artisan migrate
```

### 6. Start the server
```bash
php artisan serve
```

## API Endpoints

### Authentication
- `POST /api/register` - Register new user
- `POST /api/login` - User login
- `POST /api/logout` - User logout (requires authentication)

### Chat
- `POST /api/chat` - Send message to AI assistant (requires authentication)

## API Request Examples

### Register User
`POST /api/register`
```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

### Login
`POST /api/login`
```json
{
    "email": "john@example.com",
    "password": "password123"
}
```

### Send Chat Message
`POST /api/chat`
```json
{
    "message": "What are the symptoms of flu?"
}
```

## Security
- API authentication is handled using Laravel Sanctum
- CORS is configured for secure cross-origin requests
- Password hashing is implemented using Laravel's built-in security features

## Error Handling
The API returns appropriate HTTP status codes:
- `200`: Success
- `400`: Bad Request
- `401`: Unauthorized
- `403`: Forbidden
- `404`: Not Found
- `500`: Server Error