<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAclRoleHasAclMethodsTable extends Migration
{
    /**
     * The table name to use
     *
     * @var string
     */
    protected $name = 'acl_role_has_acl_methods';

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
            $table->bigInteger('acl_method_id')->unsigned()->index();

            $table->boolean('is_allowed')->default(true);

            $table->timestamps();

            $table->unique(
                array(
                    'acl_role_id',
                    'acl_method_id'
                ),
                'uq_acl_role_has_acl_methods'
            );

            $table->primary(
                array(
                    'acl_role_id',
                    'acl_method_id'
                ),
                'pk_acl_role_has_acl_methods'
            );

            $table->foreign('acl_role_id')
                ->references('id')
                ->on('acl_roles')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreign('acl_method_id')
                ->references('id')
                ->on('acl_methods')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });

        if (\Config::get('database.default') === 'mysql') {
            DB::statement('ALTER TABLE `'.$this->name.'` comment "Manage links between acl roles and acl methods."');
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
