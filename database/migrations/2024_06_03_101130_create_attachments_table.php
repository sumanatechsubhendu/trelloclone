<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttachmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attachable_id');
            $table->string('attachable_name');
            $table->string('attachable_type');
            $table->string('attachment_path');
            $table->string('file_type'); 
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            // Foreign key constraint for user_id
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Index for polymorphic relation
            $table->index(['attachable_id', 'attachable_type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attachments');
    }
}