<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAclModulesTable extends Migration
{
    /**
     * The table name to use
     *
     * @var string
     */
    protected $name = 'acl_modules';

    /**
     * Run the Migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->name, function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->bigIncrements('id')->unsigned();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        if (\Config::get('database.default') === 'mysql') {
            DB::statement('ALTER TABLE `'.$this->name.'` comment "Manage access control list modules."');
        }
    }

    /**
     * Reverse the Migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop($this->name);
    }
}
