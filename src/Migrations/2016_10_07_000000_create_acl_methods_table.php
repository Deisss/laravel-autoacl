<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAclMethodsTable extends Migration
{
    /**
     * The table name to use
     *
     * @var string
     */
    protected $name = 'acl_methods';

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
            $table->bigInteger('acl_module_id')->unsigned()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('acl_module_id')
                ->references('id')
                ->on('acl_modules')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });

        if (\Config::get('database.default') === 'mysql') {
            DB::statement('ALTER TABLE `'.$this->name.'` comment "Manage access control list methods."');
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
