<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateUsersAddCacheCredentialsTable extends Migration
{
    /**
     * Run the Migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = \Config::get('autoacl.Migrations.table', 'users');
        Schema::table($name, function (Blueprint $table) {
            $table->text('cache_credentials')->after('remember_token');
        });
    }

    /**
     * Reverse the Migrations.
     *
     * @return void
     */
    public function down()
    {
        $name = \Config::get('autoacl.Migrations.table', 'users');
        Schema::table($name, function (Blueprint $table) {
            $table->dropColumn('cache_credentials');
        });
    }
}
