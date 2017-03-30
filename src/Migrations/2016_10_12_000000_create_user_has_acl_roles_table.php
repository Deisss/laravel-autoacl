<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserHasAclRolesTable extends Migration
{
    /**
     * The table name to use
     *
     * @var string
     */
    protected $name = 'user_has_acl_roles';

    /**
     * Get the database name in use for this instance of laravel.
     *
     * @return string The database name in use.
     */
    protected function getDatabase()
    {
        return \Config::get('database.connections.'.\Config::get('database.default').'.database');
    }

    /**
     * Run the Migrations.
     *
     * @return void
     */
    public function up()
    {
        $database = $this->getDatabase();

        $tableUser = \Config::get('autoacl.Migrations.table', 'users');
        $tableUserIdField = \Config::get('autoacl.Migrations.field', null);
        $tableUserIdType = \Config::get('autoacl.Migrations.type', null);

        // Maybe it works for something else than MySQL, but was never tested outside of MySQL...
        if (\Config::get('database.default') === 'mysql' && empty($tableUserIdField) && empty($tableUserIdType)) {
            $resultField = \DB::select("SELECT `COLUMN_NAME` as `column` FROM `information_schema`.`KEY_COLUMN_USAGE` ".
                "WHERE `CONSTRAINT_NAME` = 'PRIMARY' AND `TABLE_SCHEMA` = '".$database.
                "' AND `TABLE_NAME` = '".$tableUser."' LIMIT 0, 1");
            if (!empty($resultField)) {
                $tableUserIdField = $resultField[0]->column;

                $resultType = \DB::select("SELECT `DATA_TYPE` as `type` FROM `information_schema`.`COLUMNS` WHERE ".
                    " `TABLE_SCHEMA` = '".$database."' AND `TABLE_NAME` = '".$tableUser."' AND `COLUMN_NAME` = 'id' LIMIT 0, 1");

                if (!empty($resultType)) {
                    $tableUserIdType = $resultType[0]->type;
                }
            }
        }

        Schema::create($this->name, function (Blueprint $table) use ($tableUser, $tableUserIdField, $tableUserIdType) {
            $table->engine = 'InnoDB';

            $userIdFieldDone = false;
            if (!empty($tableUserIdField) && !empty($tableUserIdType)) {
                if ($tableUserIdType === 'bigint') {
                    $table->bigInteger('user_id')->unsigned()->index();
                } else if ($tableUserIdType === 'mediumint') {
                    $table->mediumInteger('user_id')->unsigned()->index();
                } else if ($tableUserIdType === 'smallint') {
                    $table->smallInteger('user_id')->unsigned()->index();
                } else if ($tableUserIdType === 'tinyint') {
                    $table->tinyInteger('user_id')->unsigned()->index();
                }
                // normal int as default
                else {
                    $table->integer('user_id')->unsigned()->index();
                }
                $userIdFieldDone = true;
            }

            // Just in case...
            if (!$userIdFieldDone) {
                $table->bigInteger('user_id')->unsigned()->index();
            }

            $table->bigInteger('acl_role_id')->unsigned()->index();

            $table->timestamps();

            $table->unique(
                array(
                    'user_id',
                    'acl_role_id'
                ),
                'uq_user_has_acl_roles'
            );

            $table->primary(
                array(
                    'user_id',
                    'acl_role_id'
                ),
                'pk_user_has_acl_roles'
            );

            if ($userIdFieldDone) {
                $table->foreign('user_id')
                    ->references($tableUserIdField)
                    ->on($tableUser)
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
            }

            $table->foreign('acl_role_id')
                ->references('id')
                ->on('acl_roles')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });

        if (\Config::get('database.default') === 'mysql') {
            DB::statement('ALTER TABLE `'.$this->name.'` comment "Manage links between users and roles."');
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
