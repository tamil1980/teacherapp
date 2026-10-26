<?php $__env->startSection('title', 'Find teachers'); ?>

<?php $__env->startSection('content'); ?>
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="page-title">Find a teacher</h1>
                <p class="page-subtitle">
                    <?php echo e($teachers->total()); ?> <?php echo e(Str::plural('teacher', $teachers->total())); ?> available
                    <?php if(isset($filters['subject_id']) && $filters['subject_id']): ?>
                        for <?php echo e(optional($options['subjects']->firstWhere('id', $filters['subject_id']))->name ?? 'the selected subject'); ?>

                    <?php endif; ?>
                    — every hourly fee below is set by the teacher.
                </p>
            </div>
        </div>

        <div class="mt-8 grid gap-8 lg:grid-cols-[280px_1fr]">
            
            <aside>
                <form method="GET" action="<?php echo e(route('teachers.index')); ?>"
                      class="card sticky top-20 space-y-4 p-5">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-bold uppercase tracking-wide text-slate-900">Filters</h2>
                        <a href="<?php echo e(route('teachers.index')); ?>" class="text-xs font-semibold text-brand-600 hover:underline">Reset</a>
                    </div>

                    <div>
                        <label for="q" class="label">Search</label>
                        <input id="q" name="q" type="search" value="<?php echo e($filters['q'] ?? ''); ?>"
                               placeholder="Teacher name or keyword" class="input">
                    </div>

                    <div>
                        <label for="subject_id" class="label">Subject</label>
                        <select id="subject_id" name="subject_id" class="input">
                            <option value="">Any subject</option>
                            <?php $__currentLoopData = $options['subjects']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $subject): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($subject->id); ?>" <?php if(($filters['subject_id'] ?? null) == $subject->id): echo 'selected'; endif; ?>>
                                    <?php echo e($subject->icon); ?> <?php echo e($subject->name); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>

                    <div>
                        <label for="board_id" class="label">School board</label>
                        <select id="board_id" name="board_id" class="input">
                            <option value="">Any board</option>
                            <?php $__currentLoopData = $options['boards']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $board): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($board->id); ?>" <?php if(($filters['board_id'] ?? null) == $board->id): echo 'selected'; endif; ?>>
                                    <?php echo e($board->name); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>

                    <div>
                        <label for="class_standard_id" class="label">Class</label>
                        <select id="class_standard_id" name="class_standard_id" class="input">
                            <option value="">Any class</option>
                            <?php $__currentLoopData = $options['classes']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $class): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($class->id); ?>" <?php if(($filters['class_standard_id'] ?? null) == $class->id): echo 'selected'; endif; ?>>
                                    <?php echo e($class->name); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>

                    <fieldset>
                        <legend class="label">Teaching mode</legend>
                        <div class="space-y-2">
                            <?php $__currentLoopData = $options['modes']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $mode): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <label class="flex items-center gap-2 text-sm text-slate-700">
                                    <input type="radio" name="mode" value="<?php echo e($mode['value']); ?>"
                                           class="h-4 w-4 border-slate-300 text-brand-600 focus:ring-brand-500"
                                           <?php if(($filters['mode'] ?? null) === $mode['value']): echo 'checked'; endif; ?>>
                                    <?php echo e($mode['label']); ?>

                                </label>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="radio" name="mode" value="" class="h-4 w-4 border-slate-300 text-brand-600 focus:ring-brand-500"
                                       <?php if(empty($filters['mode'])): echo 'checked'; endif; ?>>
                                Any mode
                            </label>
                        </div>
                    </fieldset>

                    <div>
                        <label for="city" class="label">City</label>
                        <input id="city" name="city" type="text" value="<?php echo e($filters['city'] ?? ''); ?>" class="input">
                    </div>

                    <div>
                        <label for="min_fee" class="label">Hourly fee range (₹)</label>
                        <div class="flex items-center gap-2">
                            <input id="min_fee" name="min_fee" type="number" min="0" step="50"
                                   value="<?php echo e($filters['min_fee'] ?? ''); ?>" placeholder="<?php echo e((int) $priceRange['min']); ?>" class="input">
                            <span class="text-slate-400">—</span>
                            <input name="max_fee" type="number" min="0" step="50"
                                   value="<?php echo e($filters['max_fee'] ?? ''); ?>" placeholder="<?php echo e((int) $priceRange['max']); ?>" class="input">
                        </div>
                        <p class="hint">Matching teachers charge between ₹<?php echo e(number_format($priceRange['min'], 0)); ?> and ₹<?php echo e(number_format($priceRange['max'], 0)); ?> per hour.</p>
                    </div>

                    <div>
                        <label for="min_rating" class="label">Minimum rating</label>
                        <select id="min_rating" name="min_rating" class="input">
                            <option value="">Any rating</option>
                            <?php $__currentLoopData = [4.5, 4, 3.5, 3]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rating): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($rating); ?>" <?php if(isset($filters['min_rating']) && (float) $filters['min_rating'] === (float) $rating): echo 'selected'; endif; ?>>
                                    <?php echo e(number_format($rating, 1)); ?> ★ and above
                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>

                    <div>
                        <label for="experience" class="label">Minimum experience (years)</label>
                        <input id="experience" name="experience" type="number" min="0" max="50"
                               value="<?php echo e($filters['experience'] ?? ''); ?>" class="input">
                    </div>

                    <button type="submit" class="btn-primary w-full">Apply filters</button>
                </form>
            </aside>

            
            <section>
                <?php if($teachers->isEmpty()): ?>
                    <div class="card p-12 text-center">
                        <div class="text-4xl">🔍</div>
                        <h2 class="mt-4 text-lg font-bold text-slate-900">No teachers match these filters</h2>
                        <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">
                            Try widening the hourly fee range, clearing the class or board filter, or searching for a different subject.
                        </p>
                        <a href="<?php echo e(route('teachers.index')); ?>" class="btn-secondary mt-6">Clear all filters</a>
                    </div>
                <?php else: ?>
                    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                        <?php $__currentLoopData = $teachers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $teacher): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php echo $__env->make('partials.teacher-card', [
                                'teacher' => $teacher,
                                'lowestRate' => $teacher->fees->where('is_active', true)->min('hourly_rate'),
                            ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>

                    <div class="mt-8"><?php echo e($teachers->links()); ?></div>
                <?php endif; ?>
            </section>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Teacherapp\resources\views/teachers/index.blade.php ENDPATH**/ ?>