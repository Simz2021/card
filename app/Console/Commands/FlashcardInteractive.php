<?php

namespace App\Console\Commands;

use App\Models\Flashcard;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FlashcardInteractive extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'flashcard:interactive';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Play flashcards memory game.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $user = $this->getUser();
        while (!$user) {
            $user = $this->getUser();
        }
        $this->info('!!! Welcome ' . $user->name . ' !!!');
        return $this->chooseOption($user);
    }

    /**
     * Entry point for Game
     * 
     * Asking user to choose option 
     */
    public function chooseOption($user)
    {
        // ask user to choose option
        $option = $this->choice(
            'What would you like to do?',
            [
                1 => 'Create Flashcard',
                2 => 'List All Flashcards',
                3 => 'Practice',
                4 => 'Stats',
                5 => 'Reset',
                6 => 'Exit'
            ]
        );

        // execute the function of user's choice
        switch ($option) {
            case 'Create Flashcard':
                return $this->createFlashcard($user);
                break;
            case 'List All Flashcards':
                return $this->listAllFlashcards($user);
                break;
            case 'Practice':
                return $this->practice($user);
                break;
            case 'Stats':
                return $this->stats($user);
                break;
            case 'Reset':
                return $this->reset($user);
                break;
            default:
                return 0;
        }

        return 0;
    }

    // create a new flashcard
    public function createFlashcard($user)
    {
        $question = $this->ask('Question) ');
        if ($question == "") {
            $this->info("No Question Found, Redirecting to Main Menu");
            $this->newLine();
            return $this->chooseOption($user);
        }

        $answer = $this->ask('Ans) ');
        if ($answer == "") {
            $this->info("No Answer Found, Redirecting to Main Menu");
            $this->newLine();
            return $this->chooseOption($user);
        }

        $flashcard = Flashcard::create([
            'question'  => $question,
            'answer' => $answer
        ]);

        if (!$flashcard)
            $this->error('Something went wrong!');
        else
            $this->info('Flashcard created successfully.');

        $this->ask('Press Enter for Main Menu :');
        return $this->chooseOption($user);
    }

    // print all flashcards with correct answers
    public function listAllFlashcards($user)
    {
        $flashcards = Flashcard::all();
        if ($flashcards->count() == 0) {
            $this->info('No Flashcards Found.');
            $this->ask('Press Enter for Main Menu :');
            return $this->chooseOption($user);
        }

        system('clear');
        $this->info('-----------------');
        $this->info('| All Flashcards |');
        $this->info('-----------------');
        $this->newLine();

        $flashcards = $flashcards->map(function ($flashcard, $i) {
            return [$i + 1, $flashcard->question, $flashcard->answer];
        });

        $this->table(
            ['#', 'Question', 'Answer'],
            $flashcards
        );

        $this->ask('Press Enter for Main Menu :');
        return $this->chooseOption($user);
    }

    // practice 
    public function practice($user)
    {
        $user = User::find($user->id);
        $flashcards = Flashcard::all();

        if ($flashcards->count() == 0) {
            $this->info('No Flashcards Found.');
            $this->ask('Press Enter for Main Menu :');
            return $this->chooseOption($user);
        }

        $answeredFlashcards = $user->flashcards->pluck('id')->toArray();
        $correctFlashcards = $user->flashcardsWithCorrectAnswer()->pluck('id')->toArray();

        $flashcards = $flashcards->map(function ($flashcard, $i) use ($answeredFlashcards, $correctFlashcards) {
            $status = 'Not Answered';
            if (in_array($flashcard->id, $answeredFlashcards)) {
                if (in_array($flashcard->id, $correctFlashcards))
                    $status = 'Correct';
                else
                    $status = 'Incorrect';
            }
            return [
                $i + 1,
                $flashcard->question,
                $status
            ];
        });

        system('clear');

        $this->info('--------------------------');
        $this->info('| Your Flashcards Report |');
        $this->info('--------------------------');
        $this->newLine();

        $this->table(
            ['#', 'Question', 'Status'],
            $flashcards
        );

        $this->info('===========================');
        $this->info('| Correctly Answered  ' . (int) (count($correctFlashcards) * 100 / count($flashcards)) . '% |');
        $this->info('===========================');

        return $this->getPracticableQuestion($user, $flashcards);
    }

    public function getUser()
    {
        $userEmail = $this->ask('Enter your username or email to start');
        $user = User::where('email', $userEmail)->first();
        if (!$user) {
            $signup = $this->ask('No user found with this username or email, Do you want to create new account (Y/N)');
            if ($signup == 'y' || $signup == 'Y') {
                $userName = $this->ask('Enter your full name');
                if (!$userName) {
                    $userName = $this->ask('Enter your full name');
                }
                $user = User::create([
                    'name' => $userName,
                    'email' => $userEmail,
                    'password' => 'password'
                ]);
            } else {
                return $this->getUser();
            }
        }
        return $user;
    }

    public function getPracticableQuestion($user, $flashcards)
    {
        $question = $this->ask("Enter the Question No. to practice OR Press Enter for main menu");
        if ($question == "") {
            return $this->chooseOption($user);
        }
        if (!is_numeric($question) || ((int)$question > count($flashcards)) || ((int)$question < 1)) {
            $this->info("You have entered incorrect value. Please try from above list or type exit");
            return $this->getPracticableQuestion($user, $flashcards);
        } else {
            $questionIndex = (int)$question - 1;
            if ($flashcards[$questionIndex][2] == 'Correct') {
                $this->info("You have already answered correct this question. Please try another. ");
                return $this->getPracticableQuestion($user, $flashcards);
            } else {
                $question = $flashcards[$questionIndex][1];
                $answer = $this->ask("Question) " . $question);
                $flashcard = Flashcard::where("question", $question)->first();
                if ($flashcard->answer != $answer) {
                    DB::table('flashcard_user')->insert(['flashcard_id' => $flashcard->id, 'user_id' => $user->id, 'status' => 'Incorrect']);
                    $this->info('Incorrect');
                    return $this->practice($user);
                } else {
                    DB::table('flashcard_user')->updateOrInsert(
                        ['flashcard_id' => $flashcard->id, 'user_id' => $user->id],
                        ['status' => 'Correct']
                    );
                    $this->info('Correct');
                    return $this->practice($user);
                }
            }
        }

        return 0;
    }

    public function stats($user)
    {
        $user = User::find($user->id);
        $totalFlashcards = Flashcard::all()->count();

        if ($totalFlashcards == 0) {
            $this->info('No Flashcards Found.');
            $this->ask('Press Enter for Main Menu :');
            return $this->chooseOption($user);
        }

        $answeredFlashcards = $user->flashcards->count();
        $correctFlashcards = $user->flashcardsWithCorrectAnswer()->count();
        system('clear');
        $this->table(
            ['Stats', 'Value'],
            [
                ['Total Questions', $totalFlashcards],
                ['Answered Questions', (int)($answeredFlashcards * 100 / $totalFlashcards) . ' %'],
                ['Correct Answered Questions', (int)($correctFlashcards * 100 / $totalFlashcards) . ' %']
            ]
        );

        return $this->chooseOption($user);
    }

    public function reset($user)
    {
        DB::table('flashcard_user')->where('user_id', $user->id)->delete();
        $this->info("All Your Progress Reset Successfully.");
        return $this->chooseOption($user);
    }
}
