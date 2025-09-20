# EduVoltV2

A modern Laravel 12 application built with Docker Sail for educational technology platform.

## Features

- Laravel 12.30.1 with PHP 8.3+
- Docker Sail for development environment
- MySQL 8.0 database
- PSR-12 code standards compliance
- Health check endpoint
- Comprehensive testing setup

## Quick Start

### Prerequisites

- Docker and Docker Compose
- Git

### Installation

1. Clone the repository:
```bash
git clone https://github.com/hardik-kanajariya/eduvoltv2.git
cd eduvoltv2
```

2. Copy environment file:
```bash
cp .env.example .env
```

3. Start the Docker containers:
```bash
./vendor/bin/sail up -d
```

4. Install dependencies:
```bash
./vendor/bin/sail composer install
```

5. Generate application key:
```bash
./vendor/bin/sail artisan key:generate
```

6. Run database migrations:
```bash
./vendor/bin/sail artisan migrate
```

### Development Commands

#### Using Laravel Sail

Start development environment:
```bash
./vendor/bin/sail up
```

Stop development environment:
```bash
./vendor/bin/sail down
```

Run Artisan commands:
```bash
./vendor/bin/sail artisan [command]
```

Run tests:
```bash
./vendor/bin/sail test
```

#### Code Quality

Format code with PSR-12 standards:
```bash
./vendor/bin/sail bin pint
```

### Available Endpoints

- `/` - Welcome page
- `/health` - Health check endpoint (JSON)

### Health Check Response

```json
{
    "status": "ok",
    "timestamp": "2025-09-20T04:45:00.000000Z",
    "app": "EduVoltV2",
    "version": "Laravel 12",
    "database": {
        "connection": "mysql",
        "status": "connected"
    }
}
```

## Testing

Run the test suite:
```bash
./vendor/bin/sail test
```

## Environment Configuration

Key environment variables in `.env`:
- `APP_NAME=EduVoltV2`
- `DB_DATABASE=eduvoltv2`
- `DB_HOST=mysql`
- `DB_USERNAME=sail`
- `DB_PASSWORD=password`

## Contributing

1. Follow PSR-12 coding standards
2. Write tests for new features
3. Use Laravel Pint for code formatting
4. Ensure all tests pass before submitting

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).