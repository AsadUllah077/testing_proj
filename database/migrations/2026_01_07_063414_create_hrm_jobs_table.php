    <?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration
    {
        public function up()
        {
            Schema::create('hrm_jobs', function (Blueprint $table) {
                $table->id();
                $table->string('job_id')->unique()->nullable(); // JB-2024-054
                $table->string('job_title');
                $table->foreignId('department_id')->constrained()->onDelete('cascade');
                $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'internship', 'remote'])->default('full_time');
                $table->enum('experience_level', ['entry', 'mid', 'senior', 'lead', 'executive'])->default('mid');
                $table->string('location')->nullable(); // City, State or Remote
                $table->text('job_description')->nullable();
                $table->text('requirements')->nullable();
                $table->json('skills')->nullable(); // Array of skills

                // Assignment & Status
                $table->foreignId('hiring_manager_id')->nullable()->constrained('users');
                $table->enum('status', ['draft', 'open', 'on_hold', 'closed', 'cancelled'])->default('draft');
                $table->date('posted_date')->nullable();
                $table->date('closing_date')->nullable();

                // Stats
                $table->integer('applicants_count')->default(0);
                $table->integer('views_count')->default(0);

                // Company
                $table->foreignId('company_id')->constrained()->onDelete('cascade');
                $table->foreignId('created_by')->constrained('users');

                $table->timestamps();
                $table->softDeletes();
            });
        }

        public function down()
        {
            Schema::dropIfExists('hrm_jobs');
        }
    };
