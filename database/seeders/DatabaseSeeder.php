<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Note;
use App\Models\Label;
use App\Models\UserPreference;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create main test user (already activated)
        $user1 = User::create([
            'name' => 'Demo User',
            'email' => 'demo@notekeeper.app',
            'password' => Hash::make('123456'),
            'display_name' => 'Demo User',
            'is_activated' => true,
            'email_verified_at' => now(),
        ]);

        UserPreference::create([
            'user_id' => $user1->id,
            'theme' => 'dark',
            'font_size' => 'medium',
            'note_color' => '#ffffff',
            'view_mode' => 'grid',
        ]);

        // Create second test user for sharing
        $user2 = User::create([
            'name' => 'Jane Smith',
            'email' => 'jane@notekeeper.app',
            'password' => Hash::make('123456'),
            'display_name' => 'Jane Smith',
            'is_activated' => true,
            'email_verified_at' => now(),
        ]);

        UserPreference::create([
            'user_id' => $user2->id,
        ]);

        // Create labels for user1
        $workLabel = Label::create(['user_id' => $user1->id, 'name' => 'Work', 'color' => '#ef4444']);
        $personalLabel = Label::create(['user_id' => $user1->id, 'name' => 'Personal', 'color' => '#3b82f6']);
        $ideasLabel = Label::create(['user_id' => $user1->id, 'name' => 'Ideas', 'color' => '#8b5cf6']);
        $urgentLabel = Label::create(['user_id' => $user1->id, 'name' => 'Urgent', 'color' => '#f59e0b']);

        // Create notes for user1
        $note1 = Note::create([
            'user_id' => $user1->id,
            'title' => 'Welcome to NoteKeeper',
            'content' => "Welcome to NoteKeeper! This is your personal note management space.\n\nHere are some things you can do:\n- Create and organize notes\n- Pin important notes to the top\n- Add labels for easy filtering\n- Protect sensitive notes with passwords\n- Share notes with other users\n- Attach images to your notes\n\nEnjoy using NoteKeeper!",
            'is_pinned' => true,
            'pinned_at' => now(),
        ]);
        $note1->labels()->attach([$personalLabel->id]);

        $note2 = Note::create([
            'user_id' => $user1->id,
            'title' => 'Project Meeting Notes',
            'content' => "Meeting Date: April 10, 2026\nAttendees: Team Alpha\n\nAgenda:\n1. Sprint review - completed 15 tasks\n2. Upcoming deadlines - April 25th milestone\n3. Resource allocation for Q2\n4. Client feedback review\n\nAction Items:\n- Update project timeline\n- Schedule follow-up with design team\n- Prepare Q2 budget proposal",
        ]);
        $note2->labels()->attach([$workLabel->id, $urgentLabel->id]);

        $note3 = Note::create([
            'user_id' => $user1->id,
            'title' => 'App Feature Ideas',
            'content' => "Brainstorming session results:\n\n1. Dark mode with custom themes ✅\n2. Collaboration features\n3. Markdown support\n4. Voice notes\n5. Calendar integration\n6. AI-powered note summarization\n7. Export to PDF\n8. Custom templates",
        ]);
        $note3->labels()->attach([$ideasLabel->id]);

        $note4 = Note::create([
            'user_id' => $user1->id,
            'title' => 'Shopping List',
            'content' => "Groceries:\n- Milk\n- Eggs\n- Bread\n- Fruits (apples, bananas)\n- Vegetables\n- Chicken breast\n- Rice\n- Pasta\n- Olive oil\n- Coffee beans",
        ]);
        $note4->labels()->attach([$personalLabel->id]);

        $note5 = Note::create([
            'user_id' => $user1->id,
            'title' => 'Confidential: Budget Report',
            'content' => "Q1 2026 Budget Summary\n\nRevenue: $1,250,000\nExpenses: $980,000\nNet Profit: $270,000\n\nDepartment Breakdown:\n- Engineering: $450,000\n- Marketing: $200,000\n- Operations: $180,000\n- HR: $150,000",
            'has_password' => true,
            'note_password' => Hash::make('secret'),
        ]);
        $note5->labels()->attach([$workLabel->id]);

        // Create labels for user2
        Label::create(['user_id' => $user2->id, 'name' => 'Study', 'color' => '#10b981']);
        Label::create(['user_id' => $user2->id, 'name' => 'Recipes', 'color' => '#f97316']);

        Note::create([
            'user_id' => $user2->id,
            'title' => 'Jane\'s First Note',
            'content' => 'Hello from Jane! This is my personal note.',
        ]);
    }
}
