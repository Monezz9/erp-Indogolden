<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('username', 50)->nullable()->after('name');
            });
        }

        $taken = [];

        DB::table('users')
            ->select(['id', 'name', 'email', 'username'])
            ->orderBy('id')
            ->get()
            ->each(function (object $user) use (&$taken): void {
                $current = Str::lower((string) ($user->username ?? ''));

                $base = $current !== ''
                    ? $this->normalizeUsername($current, (int) $user->id)
                    : $this->normalizeBaseUsername($user);

                $username = $base;
                $counter = 2;

                while (isset($taken[$username])) {
                    $suffix = '-'.$counter;
                    $username = Str::limit($base, 50 - strlen($suffix), '').$suffix;
                    $counter++;
                }

                if ($username !== $current) {
                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['username' => $username]);
                }

                $taken[$username] = true;
            });

        Schema::table('users', function (Blueprint $table): void {
            $table->unique('username');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('users', 'username')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique('users_username_unique');
            $table->dropColumn('username');
        });
    }

    protected function normalizeBaseUsername(object $user): string
    {
        $emailPrefix = Str::before((string) ($user->email ?? ''), '@');
        $seed = $emailPrefix !== '' ? $emailPrefix : ((string) ($user->name ?? ''));

        return $this->normalizeUsername($seed, (int) $user->id);
    }

    protected function normalizeUsername(string $seed, int $userId): string
    {
        $base = Str::of($seed)
            ->lower()
            ->replaceMatches('/[^a-z0-9._-]+/', '-')
            ->trim('-._')
            ->toString();

        if ($base === '') {
            $base = 'user-'.$userId;
        }

        return Str::limit($base, 50, '');
    }
};
