<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateCustomEmojiTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('custom_emoji', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('shortcode')->index()->comment('String shortcode for this emoji');
            $table->string('domain')->nullable()->index()->comment('Origin domain of this emoji');
            $table->boolean('disabled')->default(false)->index()->comment('Has a moderation action disabled this emoji from being shown');
            $table->boolean('visible_in_picker')->default(true)->comment('Is this emoji visible in the admin emoji picker?');
            $table->string('uri')->nullable()->comment('ActivityPub uri of this emoji. Something like "https://example.org/emojis/1234"');
            $table->string('image_url')->nullable()->comment('Where can this emoji be retrieved from the local server');
            $table->string('image_remote_url')->nullable()->comment('Where can this emoji be retrieved remotely');
            $table->unsignedInteger('category_id')->nullable()->comment('In which emoji category is this emoji visible?');
            $table->dateTime('image_updated_at')->nullable()->comment('When was the emoji image last updated?');
            $table->unique(['shortcode', 'domain']);
            $table->datetimes();
            $table->comment('自定义emoji表');
        });

        Schema::create('custom_emoji_category', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->unique()->index()->comment('分类名');
            $table->datetimes();
            $table->comment('emoji 分类表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_emoji');
        Schema::dropIfExists('custom_emoji_category');
    }
}
