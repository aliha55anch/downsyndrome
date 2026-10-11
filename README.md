# Down Syndrome Children Support Platform

A web-based support platform designed to help parents and caregivers of children with Down syndrome access age-based developmental guidance, home activities, early-learning resources, progress tracking, and professional support.

> **Project status:** Academic project / under development. Features and requirements should be confirmed with relevant stakeholders before implementation.

## Overview

The platform aims to make useful developmental information easier for families to access. Parents can explore activities based on a child's age and developmental area, practise suitable activities at home, and record progress over time.

The platform is intended for education and support. It does **not** diagnose medical or developmental conditions and does not replace advice from qualified professionals.

## Main Features

- **Age-based guidance:** General developmental guidance for age groups such as 0–6 months, 6–12 months, 1–2 years, 2–3 years, 3–5 years, and 5+ years.
- **Developmental activity library:** Activities covering speech and language, cognitive development, gross and fine motor skills, early education, and social/self-help skills.
- **Early-learning resources:** Learning activities for colours, shapes, numbers, alphabets, matching, sorting, object recognition, and daily-life skills.
- **Parent and child profiles:** Organise a child's basic profile and relevant activity information.
- **Progress tracking:** Record completed activities, dates, duration, observations, and progress notes.
- **Professional directory:** Help families find relevant professionals, subject to verified information and available directory data.
- **Professional support (planned):** Potential future options for questions, referrals, or online support, subject to privacy and professional safeguards.
- **Personalised recommendations (future enhancement):** Suggest relevant activities based on the child's age and the developmental area selected by the parent.

## Technology Stack

The proposed stack for the initial version is:

- **Frontend:** HTML, CSS, Bootstrap, JavaScript/jQuery
- **Backend:** PHP with Laravel
- **Database:** MySQL

The final stack and implementation details may change as project requirements are confirmed.

## User Roles

Potential user roles include:

- **Parent/Caregiver:** View guidance, explore activities, maintain child information, and record progress.
- **Professional:** Manage a professional profile and, if approved for a later version, provide support.
- **Administrator:** Manage platform content, users, and professional-directory information.

Role permissions should be finalised during requirements analysis.

## Getting Started

The application is under development, so installation steps may change. Once the Laravel application and database configuration are available:

1. Install a compatible PHP version, Composer, and MySQL.
2. Clone the repository:
   ```bash
   git clone <repository-url>
   ```
3. Open the project directory:
   ```bash
   cd <project-folder>
   ```
4. Install PHP dependencies:
   ```bash
   composer install
   ```
5. Create a local environment file:
   ```bash
   cp .env.example .env
   ```
   On Windows, you can copy `.env.example` and rename the copy to `.env`.
6. Create a MySQL database and update the database settings in `.env`.
7. Generate the Laravel application key:
   ```bash
   php artisan key:generate
   ```
8. Run database migrations if migrations have been created:
   ```bash
   php artisan migrate
   ```
9. Start the local development server:
   ```bash
   php artisan serve
   ```

Open the local URL displayed by Laravel in your browser. These steps assume the repository contains a standard Laravel application; adjust them if the project has not yet been initialised.

## Project Structure

The exact structure depends on the implementation. A standard Laravel application commonly includes:

```text
project-folder/
├── app/              # Application logic
├── database/         # Migrations, seeders, factories
├── public/           # Public assets and entry point
├── resources/        # Views and frontend resources
├── routes/           # Web/API route definitions
├── storage/          # Logs and generated files
├── tests/            # Automated tests
├── .env.example      # Example environment configuration
├── artisan           # Laravel command-line entry point
└── composer.json     # PHP dependencies and scripts
```

## Development Roadmap

1. Confirm requirements with stakeholders.
2. Design the database and user roles.
3. Build the initial parent-facing pages and activity library.
4. Implement child profiles and progress tracking.
5. Add the professional directory and administrator tools.
6. Test usability, accessibility, privacy, and security.
7. Consider future features such as professional interaction and personalised activity recommendations.

## Privacy, Safety, and Accessibility

- Collect only the personal information required for the platform.
- Protect children's information and restrict access according to user roles.
- Obtain appropriate consent before collecting or sharing children's photos, videos, or records.
- Use reliable, reviewed educational content and clearly communicate its limitations.
- Do not present the platform as a diagnostic tool or a replacement for professional care.
- Aim for a clear, accessible, mobile-friendly interface for parents and caregivers.

## Contributing

For team collaboration:

1. Clone the repository.
2. Create a separate branch for your task.
3. Make focused changes and test them locally.
4. Commit your changes with a clear message.
5. Push your branch and open a Pull Request for review.
6. Merge changes only after the team has reviewed them.

Avoid committing `.env`, passwords, API keys, database credentials, or private information. Keep `.env.example` free of real secrets.

## License

No license has been selected yet. Add a license only after the project team decides how the code may be used and shared.
