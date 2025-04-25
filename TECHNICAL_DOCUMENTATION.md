# Technical Documentation - Selvie Site

---

## 1. System Overview

*   **Application Purpose:**
    *   Selvie is a web-based platform designed to streamline interactions and processes between educational mentors and students, focusing on **Social Emotional Learning (SEL)** support.
    *   Its core functions include managing student information, scheduling and tracking meetings, facilitating task management, generating standardized support documents like **Individualised Social Emotional Learning Plans (ISELP)** and Personal Development Plans (PDP), managing mentor training, and providing data analytics and reporting. AI features assist in plan generation and progress reporting.
    *   The system aims to provide a centralized hub for mentors, administrators, and potentially other stakeholders involved in student SEL support. User personas include: `System Admin`, `Mentor`, `Parent`, `School Staff`, `Community Organizer`.

*   **Technology Stack:**
    *   **Backend:** PHP >= 8.2 utilizing the **Laravel Framework (v11.31+)**.
    *   **Frontend:**
        *   **Livewire (v3.6+)**: Primary framework for building all dynamic UI components.
        *   **Alpine.js (Implied by Livewire/Jetstream)**: Used for client-side interactivity within Livewire/Blade views.
        *   **Tailwind CSS (v3.4+)**: Utility-first CSS framework.
        *   **FullCalendar (v6.1+)**: Used for meeting scheduling views (Integrated via Alpine).
        *   **TomSelect (v2.4+)**: Used for enhanced select inputs (Integrated via Alpine).
        *   **Marked (v15.0+)**: Used for Markdown rendering.
    *   **Database:** Supports **PostgreSQL** (Primary for Heroku deployment) or **MySQL**. Uses Eloquent ORM. Also used for Queue storage.
    *   **Web Server:** **Apache** (Configured for Heroku via `heroku-php-apache2`) or Nginx.
    *   **Caching:** **File** based. Although Heroku environment is configured for Redis (`CACHE_DRIVER=redis`), the application overrides this to use the `file` driver.
    *   **Queues:** **Database** driver (`database`). Jobs are stored in the `jobs` table and processed by the `worker`.
    *   **Search:** Implemented via standard Eloquent database queries (e.g., `WHERE LIKE`).

*   **System Architecture:**
    *   Follows **Model-View-Controller (MVC)** pattern, heavily extended with **Livewire** components (`app/Livewire`) for building the entire dynamic UI. Controllers might handle initial page loads or API-like actions, but UI rendering and interaction are primarily driven by Livewire.
    *   **Laravel Jetstream (v5.3+)** provides authentication scaffolding using the **Livewire stack**. **Teams feature is NOT used**.
    *   **Service Layer** (`app/Services`) encapsulates business logic (e.g., AI interactions, integrations, plan generation).
    *   Utilizes **Laravel Queues** (using the **database** driver) for background jobs.
    *   Adheres to standard Laravel directory structure.
    *   **Diagram:**

        ```mermaid
        graph TD
            subgraph "User"
                U[Browser]
            end

            subgraph "Web Server (Heroku)"
                WS[Apache / Nginx]
            end

            subgraph "Laravel Application (Web Dyno)"
                LR[Laravel Router]
                LC[Livewire Components]
                CTRL[Controllers]
                SRV[Service Layer]
                MDL[Eloquent Models]
                BLD[Blade Views + Alpine.js]
                FCACHE[File Cache]
                QD[Queue Dispatcher]
            end

            subgraph "Database (Heroku Postgres)"
                DB[(PostgreSQL)]
                QDB[(Queue Table in DB)]
            end

            subgraph "Background Worker (Worker Dyno)"
                QW[Queue Worker]
            end

            subgraph "External Services"
                OAI[OpenAI API]
                CLD[Cloudinary API]
                TWL[Twilio API]
                GRV[Gravatar]
            end

            U -- HTTPS --> WS
            WS -- Forwards PHP Request --> LR
            WS -- Serves Static Assets (CSS/JS from Vite Build) --> U

            LR --> LC
            LR --> CTRL

            LC -- Renders & Interacts via AJAX/WS --> U
            LC --> BLD
            LC -- Uses --> SRV
            LC -- Uses --> MDL
            LC -- Reads/Writes --> FCACHE

            CTRL -- Uses --> SRV
            CTRL -- Uses --> MDL

            SRV -- Uses --> MDL
            SRV -- Accesses --> FCACHE
            SRV -- Calls --> OAI
            SRV -- Calls --> CLD
            SRV -- Calls --> TWL
            SRV -- Calls --> GRV
            SRV -- Dispatches Job --> QD

            MDL -- Interacts with --> DB

            QD -- Writes Job --> QDB

            QW -- Reads Job --> QDB
            QW -- Uses --> SRV
            QW -- Uses --> MDL
            QW -- Interacts with --> DB
        ```

*   **Environment Requirements:**
    *   **PHP:** >= 8.2 (with required extensions like PDO, Mbstring, etc.).
    *   **Database:** PostgreSQL or MySQL server.
    *   **Web Server:** Apache or Nginx with URL rewriting.
    *   **Node.js & npm:** Required for frontend asset compilation via Vite.
    *   **Composer:** For PHP dependency management.
    *   **Redis Server:** *Not required* for core functionality if Cache/Session overrides are active. Addon may be present on Heroku but unused.

## 2. Installation & Setup

*   **Environment Setup:**
    1.  Clone repository.
    2.  Navigate to project directory.
    3.  Install PHP, Composer, Node.js/npm, Database (PostgreSQL recommended), Redis (optional).
    4.  Configure Web Server (document root: `/public`, handle `index.php`).
*   **Dependencies Installation:**
    1.  `composer install` (may need `--ignore-platform-req=ext-redis` if Redis extension isn't installed locally but fallback is used). Use `--no-dev` for production.
    2.  `npm install`.
*   **Configuration:**
    1.  `cp .env.example .env`.
    2.  `php artisan key:generate`.
    3.  Configure `DB_*`, `REDIS_*`, `MAIL_*`, `QUEUE_CONNECTION`, `CACHE_DRIVER`, `APP_URL`.
    4.  Configure OpenAI keys (`OPENAI_API_KEY`), Cloudinary (`CLOUDINARY_URL`), Twilio (`TWILIO_*`) if used.
    5.  Review other `.env` settings.
*   **Database Setup:**
    1.  Create database.
    2.  `php artisan migrate`.
    3.  `(Optional)` `php artisan db:seed` (Seeders like `RolesAndPermissionsSeeder`).
*   **Building Assets:**
    *   Uses **Vite**.
    *   Development: `npm run dev` (starts Vite dev server with HMR).
    *   Production: `npm run build` (compiles/bundles assets to `public/build`).
    *   **Local Development Server:** Run `php artisan serve` to start the built-in PHP development server (usually accessible at `http://127.0.0.1:8000`). This is often used alongside `npm run dev` during local development.

## 3. Core Components

*   **Models:** (Located in `app/Models`)
    *   Uses **Eloquent ORM**.
    *   Key Models: `User`, `Team`, `Student`, `Meeting`, `Task`, `Iselp`, `Pdp`, `MentorSurvey`, `Training`, `PartnerOrganization`, `Role` (Spatie), `Permission` (Spatie), `Activity` (Spatie), `Media` (Spatie).
    *   Traits: `HasFactory`, `Notifiable`, `HasApiTokens` (Sanctum), `TwoFactorAuthenticatable` (Jetstream), `HasRoles` (Spatie), `InteractsWithMedia` (Spatie), `LogsActivity` (Spatie).
*   **Controllers/Livewire Components:**
    *   **Controllers** (`app/Http/Controllers`): Standard request handling (e.g., `UserProfileController`, `TeamController` from Jetstream).
    *   **Livewire Components** (`app/Livewire`): Handle dynamic UI. Key components exist for: Mentor Dashboard, Admin Dashboard, Student Management (Table, Profile), Meeting Calendar/Forms, Task List/Forms, ISELP/PDP Generation, Weekly Survey, Training Management, Admin panels (User/Role/Permission Management, Partner Management).
*   **Middleware:** (Located in `app/Http/Middleware`)
    *   Standard Laravel middleware (`Authenticate`, `VerifyCsrfToken`, etc.).
    *   Jetstream middleware (`EnsureTeamMember`).
    *   Spatie middleware (`role:`, `permission:`).
*   **Services:** (Located in `app/Services`)
    *   Encapsulate business logic.
    *   Key Services: `SurveyService`, Services for AI interactions (OpenAI/ISELP generation, Mentor Reports), `IselpGeneratorService`, `AutoUpdateIselpBasedOnWeeklySurveyService.php`, `IselpGeneratorService.php`, `IselpRecommendationService.php`, `IselpService.php`, `IselpUpdateService.php`, (uses AI services for ISELP), `PdpGeneratorService`, `MeetingService`, `TaskService`, `MentorNotificationService.php`, `NotificationService`, potentially services for data integration (BAG). `AutoGenerateGoalService.php`, `AutoGenerateTaskService.php`, `MentorPlanGeneratorService.php`, `PdpGeneratorService.php`(For Auto generate using AI), `ProgressReportAndRecommendationGenerator.php`, `StudentChatService.php`, `SurveyService.php`, `TicketAnalysisService.php`, `TrainingRecommendationService.php`, `ContactSupportChatService.php`, `WrapAroundSupportService.php`(For Contact Support Using AI).
*   **Jobs & Queues:** (Jobs in `app/Jobs`)
    *   Asynchronous tasks processed via Laravel Queues using the **`database` driver**. Requires `jobs` and `failed_jobs` tables in the database.
    *   A background `worker` process (`php artisan queue:work`) is needed to process jobs from the `jobs` table.
    *   Potential Jobs: `SendMeetingReminder`, `SendWeeklyMentorReport` (Email/SMS), `ProcessIselpGeneration`, `GenerateReport`, `SyncExternalData`, `ProcessAiAnalysis`.

## 4. Authentication & Authorization

*   **Authentication System:**
    *   Uses **Laravel Jetstream (v5.3+)** with Livewire stack and **Teams** feature.
    *   Backend logic provided by **Laravel Fortify**.
    *   Session-based authentication for web.
    *   **Laravel Sanctum (v4.0+)** installed, potentially for internal API/SPA authentication if needed, but primarily session-based.
*   **User Roles:**
    *   Managed by `spatie/laravel-permission` (v6.16+).
    *   Roles: `System Admin`, `Mentor`, `Parent`, `School Staff`, `Community Organizer` (defined in seeders/admin UI).
*   **Permissions:**
    *   Managed by `spatie/laravel-permission`. Granular control over actions.
    *   Defined alongside roles. Key permissions align with Epic features (e.g., `view students`, `manage tasks`, `generate iselp`, `manage trainings`, `manage partner organisations`).
    *   Enforced via Blade directives (`@can`), Middleware (`can:`, `role:`), Policies (`app/Policies`), Controller methods (`$this->authorize`).

## 5. Features Documentation

1.  **Authentication & Role Management**
    *   **Overview**: Secure login system using Laravel Jetstream. Role-based access control implemented via Spatie Permissions.
    *   **Key Features**:
        *   Roles: Predefined roles such as Admin, Mentor, Student etc.
        *   Permissions: Fine-grained access control for features and data.
        *   Team Management: Grouping users into teams for better collaboration.
    *   **Workflow**: Users log in using secure credentials. Roles and permissions are assigned and managed by Admins. Access to features is restricted based on roles.

2.  **Comprehensive Student Search**
    *   **Overview**: Advanced search functionality to find students by name, ID, or other attributes.
    *   **Key Features**:
        *   Search: Exact, partial, or fuzzy search by name or ID.
        *   Filters: Filter students by grade, school, or other criteria.
        *   Role-Based Access: Search results are restricted based on user roles and permissions.
    *   **Workflow**: Users enter search terms or apply filters. Results are displayed dynamically using Eloquent queries.

3.  **Mentor Dashboard & Student Management**
    *   **Overview**: A personalized dashboard for mentors to manage tasks, students, and meetings.
    *   **Key Features**:
        *   Dashboard Overview: Displays "My Tasks," "My Students," "My Meetings," and "My Trainings."
        *   Student Profiles: View detailed student profiles, including BAG/DESSA data.
        *   Wraparound Support: Suggests partner organizations for student support.
        *   Help Request Chatbot: AI-powered chatbot for assistance.
    *   **Workflow**: Mentors log in to view their dashboard. Access student profiles and manage tasks or meetings.

4.  **Calendar & Meetings**
    *   **Overview**: Meeting scheduling and calendar management for mentors and admins.
    *   **Key Features**:
        *   Calendar View: Interactive calendar (using FullCalendar).
        *   Meeting Scheduling: Schedule meetings with students or teams.
        *   Reminders: Automated reminders via email.
        *   Meeting History: View past meetings and notes.
    *   **Workflow**: Schedule meetings via the calendar interface. Receive reminders and track meeting history.

5.  **Task Management**
    *   **Overview**: Manage tasks for mentors and students, including manual and auto-generated tasks.
    *   **Key Features**:
        *   Task Creation: Create tasks manually or auto-generate from ISELP/PDP.
        *   Task Tracking: View, filter, and mark tasks as complete.
        *   Role-Based Access: Tasks are assigned and managed based on roles.
    *   **Workflow**: Create tasks and assign them to students or mentors. Track task progress and completion.

6.  **Weekly Mentor Surveys & ISELP Updates**
    *   **Overview**: Weekly surveys for mentors to provide updates on students.
    *   **Key Features**:
        *   Survey Submission: Mentors submit weekly surveys.
        *   AI Analysis: Surveys analyzed by AI to update ISELP or student profiles.
        *   Integration: Updates are reflected in student data and reports.
    *   **Workflow**: Mentors complete surveys. AI processes the data and updates relevant records.

7.  **Training Management**
    *   **Overview**: Manage training modules for mentors and admins.
    *   **Key Features**:
        *   Training Modules: View and complete assigned training.
        *   Admin Tools: Create, assign, and track training progress.
        *   Progress Tracking: Monitor completion rates and performance.
    *   **Workflow**: Admins assign training modules to mentors. Mentors complete training and track progress.

8.  **AI-Powered ISELP Generation**
    *   **Overview**: AI-driven generation of Individualized Social Emotional Learning Plans (ISELP).
    *   **Key Features**:
        *   AI Integration: Uses OpenAI Assistants API for personalized plan generation.
        *   Dynamic Templates: Plans generated based on student data (BAG, DESSA, surveys).
        *   Export Options: Export plans as PDFs (`barryvdh/laravel-dompdf`) or Word documents (`phpoffice/phpword`).
    *   **Workflow**: Admins review and approve AI-generated plans. Plans are exported and shared with stakeholders.

9.  **Data Integration & Tier Tracking**
    *   **Overview**: Integration of external student data and automated tier tracking.
    *   **Key Features**:
        *   Data Sources: Integrates BAG, and other data.
        *   Tier Assignment: Automatically assigns and updates student tiers.
        *   Reports: Visualize and export data using tools like `maatwebsite/excel`.
    *   **Workflow**: Data is imported and processed. Tiers are updated, and reports are generated.

10. **AI Mentor Progress Reports**
    *   **Overview**: AI-generated reports analyzing mentor activity and student outcomes.
    *   **Key Features**:
        *   Activity Analysis: Tracks mentor tasks, meetings, and outcomes.
        *   AI Insights: Provides actionable insights for mentors.
        *   Report Export: Export reports for review and sharing.
    *   **Workflow**: AI processes mentor activity data. Reports are generated and shared with mentors.

11. **Weekly Communications**
    *   **Overview**: Automated weekly updates for mentors.
    *   **Key Features**:
        *   Reminders: Weekly emails/SMS (via Twilio) with meeting reminders.
        *   Student Updates: Summarized updates on mentee progress.
    *   **Workflow**: Notifications are sent automatically based on schedules.

12. **Partner Organizations**
    *   **Overview**: Directory of partner organizations for wraparound support.
    *   **Key Features**:
        *   Directory Management: Admins manage partner organizations.
        *   Integration: Used by the Wraparound Support feature.
    *   **Workflow**: Admins add and update partner organizations. Mentors access the directory for student support.

13. **Activity Logging**
    *   **Overview**: Logs user actions for auditing and troubleshooting.
    *   **Key Features**:
        *   Logging: Tracks user actions using `spatie/laravel-activitylog`.
        *   Audit Trails: Provides detailed logs for review.
    *   **Workflow**: Logs are automatically generated for key actions. Admins review logs as needed.

14. **File Management**
    *   **Overview**: Manage files for students, training, and reports.
    *   **Key Features**:
        *   Storage: Uses `spatie/laravel-medialibrary` with Cloudinary.
        *   File Types: Supports student documents, training materials, and reports.
        *   Heroku Compatibility: Handles Heroku's ephemeral filesystem via external storage.
    *   **Workflow**: Files are uploaded and managed via the system. Access files as needed for tasks or reports.

15. **Goals Management**
    *   **Overview**: Manage and track Mentor goals.
    *   **Key Features**:
        *   Goal Creation: Create and assign goals to Mentors.
        *   Progress Tracking: Monitor goal completion and updates.
    *   **Workflow**: Admins or mentors create goals. Progress is tracked and updated.

16. **Course of Action**
    *   **Overview**: Define and manage courses of action for Mentors.
    *   **Key Features**:
        *   Action Plans: Create detailed action plans for Mentors.
        *   Integration: Linked with ISELP and PDP systems.
    *   **Workflow**: Plans are created and assigned to students. Progress is monitored and updated.

17. **Wraparound Support**
    *   **Overview**: Comprehensive support for mentors for his students using external resources.
    *   **Key Features**:
        *   Partner Integration: Suggests partner organizations for support.
        *   AI Assistance: Uses AI to recommend resources.
    *   **Workflow**: Mentors access support recommendations. Resources are assigned to students as needed.

18. **Ticket Support**
    *   **Overview**: Support ticket system for users using AI.
    *   **Key Features**:
        *   Ticket Creation: Users can create support tickets.
        *   Admin Management: Admins manage and resolve tickets.
    *   **Workflow**: Users submit tickets via the system and AI analyze whether that handles or not if not it redirects to the Admin. Admins review and resolve issues.

19. **Recommendation Generation**
    *   **Overview**: AI-powered recommendations for students and mentors.
    *   **Key Features**:
        *   AI Integration: Generates recommendations based on data.
        *   Customizable: Admins can review and adjust recommendations.
    *   **Workflow**: AI processes data and generates recommendations. Recommendations are reviewed and implemented.

## 6. API Documentation

*   No public-facing API is documented. Laravel Sanctum is installed, which might be used for internal purposes or future development, but the primary interaction model is web-based via Livewire.

## 7. Database Schema

*   **Entity-Relationship Diagram (ERD):**

    ```mermaid
    erDiagram
        USERS ||--o{ STUDENT_ATTENDANCE_RECORDS : "Recorded By"
        USERS ||--o{ STUDENT_BEHAVIOR_RECORDS : "Recorded By"
        USERS ||--o{ STUDENT_GRADE_RECORDS : "Recorded By"
        USERS ||--o{ MENTOR_PROGRESS_REPORTS : "Subject Of"
        USERS ||--o{ MENTOR_PLANS : "Has"
        USERS ||--o{ MENTOR_SURVEYS : "Submits"
        USERS ||--o{ MEETINGS : "Creates/Attends"
        USERS ||--o{ MEETING_ATTENDANCES : "Attends"
        USERS ||--o{ TASKS : "Assigns/AssignedTo"
        USERS ||--o{ GOAL_ASSIGNMENTS : "Assigns/AssignedTo"
        USERS ||--o{ TICKETS : "Creates"
        USERS ||--o{ TICKET_RESPONSES : "Responds To"
        USERS ||--o{ TRAINING_ENROLLMENTS : "Enrolled In"
        USERS ||--o{ TRAINING_MODULE_COMPLETIONS : "Completes"
        USERS ||--o{ RECOMMENDATIONS : "Receives"
        USERS ||--o{ MENTOR_NOTIFICATION_PREFERENCES : "Has"
        USERS ||--o{ activity_log : "Caused By"
        USERS }|..|| ROLES : "Has (via model_has_roles)"
        USERS }o..o{ MEDIA : "Has (Polymorphic)"
        USERS }o..o{ STUDENTS : "Mentors (via mentor_student)"
        USERS }o..o{ COMMENTS : "Writes (Polymorphic)"
        USERS }o..o{ ATTACHMENTS : "Uploads (Polymorphic)"

        STUDENTS ||--o{ MEETINGS : "Subject Of"
        STUDENTS ||--o{ TASKS : "Related To"
        STUDENTS ||--o{ GENERATED_ISELP : "Has"
        STUDENTS ||--o{ GENERATED_PDP : "Has"
        STUDENTS ||--o{ MENTOR_SURVEYS : "Subject Of"
        STUDENTS ||--o{ STUDENT_ATTENDANCE_RECORDS : "Subject Of"
        STUDENTS ||--o{ STUDENT_BEHAVIOR_RECORDS : "Subject Of"
        STUDENTS ||--o{ STUDENT_GRADE_RECORDS : "Subject Of"
        STUDENTS ||--o{ BAG_HISTORY : "Has"
        STUDENTS ||--o{ GOAL_ASSIGNMENTS : "Subject Of"
        STUDENTS ||--o{ COURSE_OF_ACTIONS : "Has"
        STUDENTS ||--o{ STUDENT_FILES : "Has"
        STUDENTS ||--o{ RECOMMENDATIONS : "Subject Of"
        STUDENTS ||--o{ PROGRESS_NOTES : "Has"
        STUDENTS }o..o{ PARTNER_ORGANIZATIONS : "Referred To (via StudentPartnerOrganization)"
        STUDENTS }o..o{ MEDIA : "Has (Polymorphic)"
        STUDENTS }o..o{ COMMENTS : "Subject Of (Polymorphic)"
        STUDENTS }o..o{ ATTACHMENTS : "Related To (Polymorphic)"
        STUDENTS ||--|{ SCHOOLS : "Belongs To"

        MEETINGS ||--o{ MEETING_ATTENDANCES : "Has Attendances"
        MEETINGS ||--o{ MEETING_REMINDERS : "Has Reminders"
        MEETINGS ||--|{ MEETING_CATEGORIES : "Has Category"
        MEETINGS ||--|{ MEETING_OUTCOMES : "Has Outcome"
        MEETINGS }o..o{ MEDIA : "Has (Polymorphic)"
        MEETINGS }o..o{ COMMENTS : "Has (Polymorphic)"
        MEETINGS }o..o{ ATTACHMENTS : "Has (Polymorphic)"

        TASKS }o..o{ COMMENTS : "Has (Polymorphic)"
        TASKS }o..o{ ATTACHMENTS : "Has (Polymorphic)"

        GENERATED_ISELP {
            bigint id PK
            bigint student_id FK
            bigint iselp_template_id FK "NULL"
            text generated_content
            varchar status
        }
        GENERATED_ISELP ||--|| ISELP_TEMPLATES : "Based On"

        ISELP_TEMPLATES {
            bigint id PK
            varchar name
            text structure
        }
        ISELP_TEMPLATES ||--o{ ISELP_CONTENT_FILES : "Uses"

        ISELP_CONTENT_FILES {
            bigint id PK
            bigint iselp_template_id FK "NULL"
            varchar file_path
            varchar file_name
        }

        GENERATED_PDP {
            bigint id PK
            bigint student_id FK
            bigint pdp_template_id FK "NULL"
            text generated_content
        }
        GENERATED_PDP ||--|| PDP_TEMPLATES : "Based On"

        PDP_TEMPLATES {
            bigint id PK
            varchar name
            text structure
        }

        MENTOR_SURVEYS {
            bigint id PK
            bigint user_id FK
            bigint student_id FK
            text observations
            json ai_analysis "NULL"
        }

        TRAININGS {
            bigint id PK
            varchar title
            text description
            bigint curriculum_id FK "NULL"
        }
        TRAININGS ||--o{ TRAINING_MODULES : "Has Modules"
        TRAININGS ||--|{ CURRICULUM : "Belongs To"

        TRAINING_MODULES {
            bigint id PK
            bigint training_id FK
            varchar title
            text content
            int order
        }

        TRAINING_ENROLLMENTS {
            bigint id PK
            bigint user_id FK
            bigint training_id FK
            date enrolled_at
            varchar status
            date completed_at "NULL"
        }
        USERS ||--o{ TRAINING_ENROLLMENTS : "Has Enrollments"
        TRAININGS ||--o{ TRAINING_ENROLLMENTS : "Has Enrollments"

        TRAINING_MODULE_COMPLETIONS {
            bigint id PK
            bigint user_id FK
            bigint training_module_id FK
            timestamp completed_at
        }
        USERS ||--o{ TRAINING_MODULE_COMPLETIONS : "Module Completions"
        TRAINING_MODULES ||--o{ TRAINING_MODULE_COMPLETIONS : "Module Completions"

        PARTNER_ORGANIZATIONS {
            bigint id PK
            varchar name
            text contact_info
            text areas_of_expertise
        }

        STUDENT_PARTNER_ORGANIZATION {
             bigint student_id FK
             bigint partner_organization_id FK
             date referral_date
             "PK(student_id, partner_organization_id)"
        }

        GOALS {
            bigint id PK
            varchar title
            text description
            varchar type "Comment: e.g., Student, Mentor"
        }

        GOAL_ASSIGNMENTS {
            bigint id PK
            bigint goal_id FK
            bigint assignable_id "Comment: Polymorphic ID"
            varchar assignable_type "Comment: Polymorphic Type"
            date due_date "NULL"
            varchar status
        }
        GOALS ||--o{ GOAL_ASSIGNMENTS : "Assignments"

        COURSE_OF_ACTIONS {
             bigint id PK
             bigint student_id FK
             text plan_details
             date start_date
             date end_date "NULL"
        }

        TICKETS {
            bigint id PK
            bigint user_id FK "Comment: Creator"
            varchar subject
            text description
            varchar status
            varchar priority "NULL"
        }
        TICKETS ||--o{ TICKET_RESPONSES : "Has Responses"

        TICKET_RESPONSES {
            bigint id PK
            bigint ticket_id FK
            bigint user_id FK "Comment: Responder"
            text response
        }

        WORKFLOWS {
            bigint id PK
            varchar name
            varchar applicable_model "Comment: e.g., Ticket, Task"
        }
        WORKFLOWS ||--o{ WORKFLOW_STAGES : "Has Stages"

        WORKFLOW_STAGES {
            bigint id PK
            bigint workflow_id FK
            varchar name
            int order
        }
        WORKFLOW_STAGES ||--o{ WORKFLOW_TRANSITIONS : "Originates From"
        WORKFLOW_STAGES ||--o{ WORKFLOW_TRANSITIONS : "Leads To"

        WORKFLOW_TRANSITIONS {
             bigint id PK
             bigint workflow_id FK
             bigint from_stage_id FK
             bigint to_stage_id FK
             varchar action_name
        }

        RECOMMENDATIONS {
            bigint id PK
            varchar recommendable_type "Comment: Polymorphic Type"
            bigint recommendable_id "Comment: Polymorphic ID"
            varchar type "Comment: e.g., Training, Resource"
            text content
            varchar source "Comment: e.g., AI, Admin"
        }

        COMMENTS {
            bigint id PK
            bigint user_id FK
            varchar commentable_type "Comment: Polymorphic Type"
            bigint commentable_id "Comment: Polymorphic ID"
            text body
        }

        ATTACHMENTS {
            bigint id PK
            bigint user_id FK
            varchar attachable_type "Comment: Polymorphic Type"
            bigint attachable_id "Comment: Polymorphic ID"
            varchar file_path
            varchar original_name
        }

        PROGRESS_NOTES {
             bigint id PK
             bigint student_id FK
             bigint user_id FK "Comment: Author"
             date note_date
             text content
        }

        STUDENT_FILES {
             bigint id PK
             bigint student_id FK
             bigint user_id FK "Comment: Uploader"
             varchar file_path
             varchar description "NULL"
        }

        STUDENT_ATTENDANCE_RECORDS {
             bigint id PK
             bigint student_id FK
             date date
             varchar status "Comment: e.g., Present, Absent"
             bigint recorded_by_user_id FK "NULL"
        }

        STUDENT_BEHAVIOR_RECORDS {
            bigint id PK
            bigint student_id FK
            date date
            text incident_description
            text action_taken "NULL"
            bigint recorded_by_user_id FK "NULL"
        }

        STUDENT_GRADE_RECORDS {
            bigint id PK
            bigint student_id FK
            varchar subject
            varchar grade "Comment: e.g., A, 85%"
            date term_date
            bigint recorded_by_user_id FK "NULL"
        }

        BAG_HISTORY {
             bigint id PK
             bigint student_id FK
             date record_date
             int behavior_score "NULL"
             int attendance_score "NULL"
             int grade_score "NULL"
             varchar overall_tier "NULL"
        }

        MEETING_ATTENDANCES {
            bigint id PK
            bigint meeting_id FK
            bigint user_id FK
            boolean attended
        }

        MEETING_CATEGORIES {
            bigint id PK
            varchar name
        }

        MEETING_OUTCOMES {
             bigint id PK
             bigint meeting_id FK
             text outcome_notes
             varchar status
        }

        MEETING_REMINDERS {
            bigint id PK
            bigint meeting_id FK
            datetime reminder_time
            varchar status "Comment: e.g., scheduled, sent"
        }

        MENTOR_NOTIFICATION_PREFERENCES {
             bigint id PK
             bigint user_id FK
             boolean weekly_summary_email "DEFAULT true"
             boolean weekly_summary_sms "DEFAULT false"
             boolean meeting_reminder_email "DEFAULT true"
        }

        MENTOR_PLANS {
            bigint id PK
            bigint user_id FK
            text plan_details
            date review_date "NULL"
        }

        MENTOR_PROGRESS_REPORTS {
            bigint id PK
            bigint user_id FK
            date report_date
            text ai_analysis
            text recommendations
        }

        NOTIFICATION_LOGS {
            bigint id PK
            varchar channel "Comment: e.g., email, sms"
            varchar recipient
            text message
            timestamp sent_at "NULL"
        }

        SCHEDULED_EMAIL_TASKS {
            bigint id PK
            varchar recipient_email
            varchar subject
            text body
            timestamp scheduled_time
            boolean sent "DEFAULT false"
        }

        SETTINGS {
            bigint id PK
            varchar key
            text value "NULL"
        }

        SCHOOLS {
            bigint id PK
            varchar name
            text address "NULL"
        }

        CURRICULUM {
             bigint id PK
             varchar name
             text description "NULL"
        }

        ROLES { 
             bigint id PK
             varchar name 
        }
        PERMISSIONS { 
            bigint id PK 
            varchar name 
            }
        model_has_roles { 
            bigint role_id FK
            varchar model_type
            bigint model_id
            "PK(role_id, model_id, model_type)" 
            }
        role_has_permissions { 
            bigint permission_id FK 
            bigint role_id FK 
            "PK(permission_id, role_id)" 
            }
        activity_log { bigint id PK }
        media { bigint id PK }
        jobs { bigint id PK }
        failed_jobs { bigint id PK }
        password_reset_tokens { 
            varchar email PK 
            }
        sessions { 
            varchar id PK 
            }
        notifications { 
            uuid id PK 
            }

    ```

*   **Table Descriptions:**
    *   `users`: Jetstream user table (name, email, password, etc.). Also includes Mentors, Admins, etc., distinguished by roles.
    *   `students`: Core student data.
    *   `schools`: School information, linked to students.
    *   `meetings`: Meeting details (time, location, notes, status).
    *   `meeting_attendances`: Tracks which users attended which meetings.
    *   `meeting_categories`: Types of meetings (e.g., Check-in, Planning).
    *   `meeting_outcomes`: Notes/status recorded after a meeting.
    *   `meeting_reminders`: Scheduled reminders for meetings.
    *   `tasks`: Task details (description, due date, status, assignee).
    *   `goals`: Defines specific goals (can be for students or mentors).
    *   `goal_assignments`: Links Goals to Users or Students, tracks status.
    *   `iselps` / `generated_iselps`: Stores generated ISELP plan data.
    *   `iselp_templates`: Templates used for ISELP generation.
    *   `iselp_content_files`: Reference files for ISELP generation.
    *   `pdps` / `generated_pdps`: Stores generated PDP plan data.
    *   `pdp_templates`: Templates used for PDP generation.
    *   `mentor_surveys`: Stores weekly survey responses from mentors.
    *   `trainings`: Defines training courses.
    *   `training_modules`: Defines individual modules within a Training.
    *   `curriculum`: Optional grouping for Trainings.
    *   `training_enrollments`: Tracks User enrollment in Trainings.
    *   `training_module_completions`: Tracks User completion of specific Training Modules.
    *   `partner_organizations`: Directory of external support organizations.
    *   `student_partner_organization`: Pivot table linking Students to referred Partner Organizations.
    *   `course_of_actions`: Detailed action plans (likely for Students).
    *   `tickets`: Support ticket information.
    *   `ticket_responses`: Responses to support tickets.
    *   `workflows`, `workflow_stages`, `workflow_transitions`: Defines custom workflow processes (e.g., for tickets).
    *   `recommendations`: Stores AI or manually generated recommendations.
    *   `student_attendance_records`, `student_behavior_records`, `student_grade_records`, `bag_history`: Stores integrated student data points.
    *   `mentor_progress_reports`: Stores AI-generated reports on mentor progress.
    *   `mentor_plans`: Specific plans or goals set for mentors.
    *   `mentor_notification_preferences`: User preferences for receiving notifications.
    *   `comments`: Polymorphic comments related to various models.
    *   `attachments`: Polymorphic file attachments related to various models.
    *   `student_files`: Specific files uploaded directly related to a student.
    *   `progress_notes`: Notes tracking student progress.
    *   `scheduled_email_tasks`: Tasks for sending specific emails at scheduled times.
    *   `notification_logs`: Log of notifications sent (e.g., emails, potentially SMS).
    *   `settings`: Stores application-wide settings.
    *   `roles`, `permissions`, `model_has_roles`, `role_has_permissions`: Spatie permission tables.
    *   `activity_log`: Spatie activity log table.
    *   `media`: Spatie media library table.
    *   Standard Laravel tables: `password_reset_tokens`, `failed_jobs`, `sessions`, `notifications`, `jobs`.
*   **Indexes and Performance Considerations:** Standard advice applies (index PKs, FKs, frequently queried columns). Review migrations for defined indexes. *Note: Indexing columns used in search queries (`students`.`name`, etc.) and polymorphic columns (`model_type`, `model_id`) is important for performance.*

## 8. Frontend Architecture

*   **Component Structure:**
    *   **Livewire Components (`app/Livewire/*`, `resources/views/livewire/*`)**: The primary building blocks for all dynamic UI features. Encapsulate backend logic (PHP class) and frontend template (Blade view). Embedded in Blade layouts/views using `<livewire:component-name>`.
    *   **Blade:** Layouts (`resources/views/layouts`), Views (`resources/views`), Partials (`@include`), and non-dynamic Components (`<x-component>`) provide structure and render Livewire components.
    *   **Alpine.js:** Used for client-side interactions and enhancements directly within Blade/Livewire templates (`x-data`, `x-on`, `x-show`).
    *   Libraries: **FullCalendar**, **TomSelect**, **Marked** (integrated within Livewire/Alpine context).
*   **State Management:**
    *   Primarily managed within individual **Livewire** components via PHP public properties. Communication between components uses props (parent->child) or events (`$this->dispatch()`, `Livewire.on()`).
    *   **Alpine.js** for local UI state scoped to HTML elements (`x-data`).
*   **Asset Compilation:**
    *   Handled by **Vite** (`vite.config.js`).
    *   Inputs: `resources/css/app.css`, `resources/css/admin.css`, `resources/js/app.js`.
    *   Output: `public/build`. Linked via `@vite()` helper.

## 9. Testing

*   **Testing Strategy:** **PHPUnit** based.
    *   **Unit Tests (`tests/Unit`)**: Isolate testing of classes (Services, etc.).
    *   **Feature Tests (`tests/Feature`)**: Test application routes/features via HTTP requests and Livewire test helpers.
*   **Running Tests:** `php artisan test`, `php artisan test --testsuite=Unit/Feature`, etc. Uses configuration from `phpunit.xml`.
*   **Testing Standards:** Use `RefreshDatabase` trait, Model Factories (`database/factories`), descriptive assertions.

## 10. Deployment

*   **Deployment Target:** **Heroku**.
*   **Deployment Process:**
    1.  Use Heroku CLI and potentially provided scripts (`heroku-deploy.sh`).
    2.  Ensure code is pushed to the correct Git branch (`main`).
    3.  Heroku app setup (create/select app).
    4.  Buildpacks: `heroku/php`, `heroku/nodejs`.
    5.  Addons: `heroku-postgresql`.
    6.  Configure Env Vars via `heroku config:set` (including `APP_KEY`, `DB_CONNECTION=pgsql`, `APP_URL`, etc.).
    7.  Deploy code: `git push heroku main`.
    8.  Release Phase (`Procfile`): Runs `php artisan test:setup`.
    9.  Web Process (`Procfile`): Runs setup commands, starts Apache.
    10. Worker Process (`Procfile`): Runs `php artisan queue:work` processing jobs from the **database**.
    11. Post-deploy Script (`app.json`): Runs `php artisan migrate --force && php artisan storage:link`.
    12. Scheduler: Addon `scheduler:standard` can be configured to run `php artisan schedule:run`.
*   **Server Requirements:** Heroku Dynos (Web, Worker), Heroku Postgres.
*   **Maintenance Mode:** `heroku maintenance:on`, `heroku maintenance:off`.
*   **Rollback Procedures:** Use Heroku releases: `heroku releases`, `heroku rollback <release_version>`.

## 11. Troubleshooting

*   **Common Issues:** Permissions , `419 Page Expired` (CSRF/Session), Livewire issues (check browser console/network, Heroku logs), `.env` changes (need redeploy/config clear on Heroku), `500 Error` (check Heroku logs).
*   **Heroku Specific:** Access logs via `heroku logs --tail`. Use `heroku run bash`. Ephemeral filesystem requires external storage (Cloudinary/S3) for uploads. Check addon status (Postgres). Ensure `jobs` table exists and `QUEUE_CONNECTION` is set to `database`.
*   **Logging:** Configured via `config/logging.php`, `stack` channel logging to `stderr` on Heroku (viewable via `heroku logs`).
*   **Debugging Tools:** **Laravel Telescope** (`^5.7`) available for local development (`/telescope`). Laravel Debugbar (not installed). `dd()`/`dump()` locally.

## 12. Security Considerations

*   **Authentication & Authorization:** Jetstream provides strong defaults (hashing, 2FA option). Spatie Permissions for RBAC. Use middleware (`auth`, `can:`, `role:`).
*   **Data Validation:** **Crucial.** Use Laravel Form Requests or Validator for all input.
*   **CSRF Protection:** Enabled by default for web routes (`@csrf`).
*   **File Upload Security:** Validate size/type. Use `spatie/laravel-medialibrary` configured with **Cloudinary** for secure storage off Heroku's ephemeral filesystem.
*   **XSS:** Default Blade `{{ }}` escapes output.
*   **SQL Injection:** Prevented by Eloquent/Query Builder parameter binding. Avoid raw SQL with user input.
*   **Mass Assignment:** Use `$fillable`/`$guarded` in models, use `validated()` data.
*   **Dependencies:** Keep Composer/NPM packages updated (`composer update`, `npm update`, `composer audit`). `spatie/laravel-backup` (`^9.2`) is installed for backups.

## 13. Performance Optimization

*   **Caching Strategy:** Application currently uses the **`file` cache driver** due to overrides (In `AppServiceProvider`). Redis configured in Heroku environment is not actively used for caching. Build caches (`config:cache`, etc.) are still used.
*   **Database Optimization:** Eager loading (`with()`), select specific columns, ensure appropriate indexes. *Note: Database is also used for queues, monitor `jobs` table performance under load.*
*   **Asset Optimization:** Vite production build (`npm run build`) handles minification, bundling, versioning.
*   **Queues:** Application uses the **`database` queue driver**. Long-running jobs are processed in the background by the `worker` dyno, reducing web request time but increasing database load. Monitor queue length (`php artisan queue:monitor database`) and worker performance.
*   **OpCache:** Standard PHP bytecode caching (usually enabled on Heroku PHP buildpack).

## 14. Third-Party Integrations

*   **OpenAI:** For ISELP generation, AI analysis (surveys, mentor reports, PDP,..). Requires `OPENAI_API_KEY`.
*   **Cloudinary:** (`cloudinary-laravel` `^3.0`) For media/file storage. Requires `CLOUDINARY_URL` or specific keys.
*   **Twilio:** (`twilio/sdk` `^8.4`) For SMS notifications. Requires Twilio credentials (`TWILIO_SID`, `TWILIO_TOKEN`, `TWILIO_FROM`).
*   **Gravatar:** (`creativeorange/gravatar` `^1.0`) For user avatars. Configurable in `config/gravatar.php`.
*   **PDF Generation:** (`barryvdh/laravel-dompdf` `^3.1`) For exporting ISELP/PDP/Reports.
*   **Excel/CSV Handling:** (`maatwebsite/excel` `^3.1`) For data imports/exports.
*   **Word Document Generation:** (`phpoffice/phpword` `^1.3`) Potentially used for report/plan generation.
*   **Configuration:** Primarily via `.env` variables referenced in `config/services.php` and other config files.

