<?php $__env->startSection('title', 'Teacher Profile'); ?>

<?php $__env->startSection('content'); ?>
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Teacher Profile</p>
            <h1 class="page-title"><?php echo e($teacher->user?->name); ?></h1>
            <p class="page-subtitle"><?php echo e($teacher->headline); ?></p>
        </div>
        <?php if(auth()->guard('web')->check()): ?>
            <?php if(auth()->user()->role === 'parent'): ?>
                <a href="<?php echo e(route('bookings.create', ['teacher' => $teacher, 'mode' => $teacher->accepts_online ? 'online' : 'offline'])); ?>" class="btn-primary mt-5 w-full">
                    Book a class
                </a>
            <?php else: ?>
                <a href="<?php echo e(route('login')); ?>" class="btn-primary mt-5 w-full">Log in to book</a>
            <?php endif; ?>
        <?php else: ?>
            <a href="<?php echo e(route('login')); ?>" class="btn-primary mt-5 w-full">Log in to book</a>
        <?php endif; ?>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            
            <div class="card p-6">
                <h2 class="text-lg font-semibold text-slate-900">About This Teacher</h2>
                <p class="mt-4 text-slate-500"><?php echo e($teacher->bio ?? 'No biography available'); ?></p>
            </div>

            
            <div class="card p-6">
                <h2 class="text-lg font-semibold text-slate-900">Hourly Fees</h2>
                <?php $__currentLoopData = $teacher->fees(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $fee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php if($fee->is_active): ?>
                        <p class="mt-2">
                            <strong><?php echo e($fee->subject->name); ?> - <?php echo e($fee->classStandard->name); ?> - <?php echo e($fee->board->name); ?>:</strong> ₹<?php echo e(number_format($fee->hourly_rate, 0)); ?>/hour
                        </p>
                    <?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>

        <div class="space-y-6">
            
            <div class="card p-6">
                <h2 class="text-lg font-semibold text-slate-900">Available Slots</h2>
                <p class="mt-1 text-sm text-slate-500">Select a date to view availability</p>
                <div class="mt-4">
                    <!-- Date picker would go here -->
                    <p class="text-sm text-slate-500">No slots available for today</p>
                </div>
            </div>

            
            <div class="card p-6">
                <h2 class="text-lg font-semibold text-slate-900">Subjects Taught</h2>
                <p class="mt-1 text-sm text-slate-500"><?php echo e(count($teacher->teacherSubjects)); ?> subjects</p>
                <?php $__currentLoopData = $teacher->teacherSubjects->take(4); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $teacherSubject): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <a href="<?php echo e(route('subjects.show', $teacherSubject->subject->slug)); ?>"
                       class="badge bg-brand-50 text-brand-700 hover:bg-brand-100 inline-block mb-1">
                        <?php echo e($teacherSubject->subject->icon); ?> <?php echo e($teacherSubject->subject->name); ?>

                    </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php if($teacher->teacherSubjects->count() > 4): ?>
                    <span class="badge bg-slate-100 text-slate-600">+<?php echo e($teacher->teacherSubjects->count() - 4); ?> more</span>
                <?php endif; ?>
            </div>

            
            <div class="card p-6">
                <h2 class="text-lg font-semibold text-slate-900">School Boards</h2>
                <p class="mt-1 text-sm text-slate-500"><?php echo e(count($teacher->teacherBoards)); ?> boards</p>
                <?php $__currentLoopData = $teacher->teacherBoards->take(3); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $teacherBoard): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <span class="badge bg-slate-100 text-slate-600 inline-block mb-1"><?php echo e($teacherBoard->board->name); ?></span>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php if($teacher->teacherBoards->count() > 3): ?>
                    <span class="badge bg-slate-100 text-slate-600">+<?php echo e($teacher->teacherBoards->count() - 3); ?> more</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Teacherapp\resources\views/teachers/show.blade.php ENDPATH**/ ?>