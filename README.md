# Habit Tracker

A modern habit tracking application built with Symfony that helps users build and maintain good habits. The application integrates with Google Calendar and Outlook to sync habits with your calendar.

## Features

- **Habit Management**: Create, read, update, and delete habits
- **Habit Tracking**: Mark habits as completed on specific days
- **Statistics**: View completion rates, streaks, and habit history
- **Calendar Integration**: Sync habits with Google Calendar or Outlook
- **User Authentication**: Secure user accounts with roles and permissions
- **Responsive Design**: Works on desktop and mobile devices

## Tech Stack

- **Backend**: PHP 8.2+, Symfony 7.3
- **Database**: PostgreSQL
- **Authentication**: Symfony Security
- **Testing**: PHPUnit
- **Frontend**: Twig, Bootstrap 5, JavaScript
- **Dependency Management**: Composer
- **CI/CD**: GitHub Actions (configurable)

## Prerequisites

- PHP 8.2 or higher
- Composer
- PostgreSQL 12+
- Node.js & npm (for frontend assets)
- Google OAuth 2.0 credentials (for Google Calendar integration)
- Microsoft Azure AD application (for Outlook integration)

## Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/habit-tracker.git
   cd habit-tracker
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install frontend dependencies**
   ```bash
   npm install
   npm run build
   ```

4. **Configure environment variables**
   Copy `.env` to `.env.local` and update the following variables:
   ```
   DATABASE_URL="postgresql://db_user:db_password@127.0.0.1:5432/habit_tracker?serverVersion=15&charset=utf8"
   
   # Google OAuth
   GOOGLE_CLIENT_ID=your_google_client_id
   GOOGLE_CLIENT_SECRET=your_google_client_secret
   
   # Microsoft OAuth
   OAUTH_MICROSOFT_CLIENT_ID=your_microsoft_client_id
   OAUTH_MICROSOFT_CLIENT_SECRET=your_microsoft_client_secret
   OAUTH_MICROSOFT_TENANT_ID=your_tenant_id
   ```

5. **Set up the database**
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate -n
   ```

6. **Load fixtures (optional)**
   ```bash
   php bin/console doctrine:fixtures:load -n
   ```

7. **Start the development server**
   ```bash
   symfony server:start
   ```

## Testing

Run the test suite with PHPUnit:

```bash
php bin/phpunit
```

## Architecture

The application follows a clean architecture with these main components:

- **Domain Layer**: Entities, Value Objects, and Repository interfaces
- **Application Layer**: Services, DTOs, and Use Cases
- **Infrastructure Layer**: Database, External Services, and Framework-specific code
- **Presentation Layer**: Controllers, Templates, and API Endpoints

### Key Design Patterns

- **Repository Pattern**: For data access abstraction
- **Strategy Pattern**: For different calendar providers
- **Observer Pattern**: For domain events
- **Factory Pattern**: For object creation
- **Dependency Injection**: For loose coupling

## API Documentation

The application provides a RESTful API for habit management. See the [API Documentation](docs/API.md) for details.

## Security

- Password hashing with Argon2i
- CSRF protection
- XSS protection
- Rate limiting on authentication endpoints
- Secure session handling
- Input validation and sanitization

## Deployment

### Production

1. Set the `APP_ENV` to `prod` in `.env.local`
2. Clear and warm up the cache:
   ```bash
   php bin/console cache:clear --env=prod
   ```
3. Install assets:
   ```bash
   php bin/console assets:install --env=prod
   ```
4. Dump the webpack assets:
   ```bash
   npm run build
   ```

### Docker

A `docker-compose.yml` file is provided for containerized development and deployment.

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Acknowledgments

- Symfony Framework
- Doctrine ORM
- Bootstrap
- All the open-source libraries used in this project
