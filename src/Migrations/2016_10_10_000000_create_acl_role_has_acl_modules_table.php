<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAclRoleHasAclModulesTable extends Migration
{
    /**
     * The table name to use
     *
     * @var string
     */
    protected $name = 'acl_role_has_acl_modules';

    /**
     * Run the Migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->name, function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->bigInteger('acl_role_id')->unsigned()->index();
            $table->bigInteger('acl_module_id')->unsigned()->index();

            $table->boolean('is_allowed')->default(true);

            $table->timestamps();

            $table->unique(
                array(
                    'acl_role_id',
                    'acl_module_id'
                ),
                'uq_acl_role_has_acl_modules'
            );

            $table->primary(
                array(
                    'acl_role_id',
                    'acl_module_id'
                ),
                'pk_acl_role_has_acl_modules'
            );

            $table->foreign('acl_role_id')
                ->references('id')
                ->on('acl_roles')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreign('acl_module_id')
                ->references('id')
                ->on('acl_modules')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });

        if (\Config::get('database.default') === 'mysql') {
            DB::statement('ALTER TABLE `'.$this->name.'` comment "Manage links between acl roles and acl modules."');
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
