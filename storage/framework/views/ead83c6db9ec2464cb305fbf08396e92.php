<?php $__env->startSection('title', 'Book a class'); ?>

<?php $__env->startSection('content'); ?>
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Book with</p>
            <h1 class="page-title"><?php echo e($teacher->user?->name); ?></h1>
            <p class="page-subtitle"><?php echo e($teacher->headline); ?></p>
        </div>
        <a href="<?php echo e(route('teachers.show', $teacher)); ?>" class="btn-secondary">Back to profile</a>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <form method="POST" action="<?php echo e(route('bookings.store')); ?>" class="space-y-6">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="teacher_profile_id" value="<?php echo e($teacher->id); ?>">

                <div class="card p-6">
                    <h2 class="text-lg font-semibold text-slate-900">1. Select student</h2>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <?php $__currentLoopData = $students; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $student): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <label class="flex items-start gap-3 rounded-lg border border-slate-200 p-4 cursor-pointer hover:border-brand-300">
                                <input type="radio" name="student_profile_id" value="<?php echo e($student->id); ?>" class="mt-1" required <?php echo e(old('student_profile_id') == $student->id ? 'checked' : ''); ?>>
                                <div>
                                    <p class="font-medium text-slate-900"><?php echo e($student->display_name); ?></p>
                                    <p class="text-sm text-slate-500"><?php echo e($student->classStandard?->name); ?> · <?php echo e($student->board?->name); ?></p>
                                </div>
                            </label>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                    <?php $__errorArgs = ['student_profile_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="error"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="card p-6">
                    <h2 class="text-lg font-semibold text-slate-900">2. Class details</h2>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label">Subject</label>
                            <select name="subject_id" class="input">
                                <option value="">Select subject</option>
                                <?php $__currentLoopData = $subjects; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $subject): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($subject->id); ?>" <?php echo e(old('subject_id') == $subject->id ? 'selected' : ''); ?>><?php echo e($subject->name); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div>
                            <label class="label">Class</label>
                            <select name="class_standard_id" class="input">
                                <option value="">Select class</option>
                                <?php $__currentLoopData = $classStandards; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $class): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($class->id); ?>" <?php echo e(old('class_standard_id') == $class->id ? 'selected' : ''); ?>><?php echo e($class->name); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div>
                            <label class="label">Board</label>
                            <select name="board_id" class="input">
                                <option value="">Select board</option>
                                <?php $__currentLoopData = $boards; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $board): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($board->id); ?>" <?php echo e(old('board_id') == $board->id ? 'selected' : ''); ?>><?php echo e($board->name); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div>
                            <label class="label">Mode</label>
                            <select name="mode" class="input" required>
                                <?php if($teacher->accepts_online): ?><option value="online" <?php echo e(($mode ?? 'online') == 'online' ? 'selected' : ''); ?>>Online</option><?php endif; ?>
                                <?php if($teacher->accepts_offline): ?><option value="offline" <?php echo e(($mode ?? '') == 'offline' ? 'selected' : ''); ?>>Offline</option><?php endif; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <?php if($locations->isNotEmpty()): ?>
                <div class="card p-6">
                    <h2 class="text-lg font-semibold text-slate-900">3. Location (for offline)</h2>
                    <div class="mt-4 space-y-2">
                        <?php $__currentLoopData = $locations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $location): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <label class="flex items-center gap-3 rounded-lg border border-slate-200 p-3">
                                <input type="radio" name="teacher_location_id" value="<?php echo e($location->id); ?>" <?php echo e(old('teacher_location_id') == $location->id ? 'checked' : ''); ?>>
                                <div>
                                    <p class="text-sm font-medium text-slate-900"><?php echo e($location->label); ?></p>
                                    <p class="text-xs text-slate-500"><?php echo e($location->city); ?> <?php if($location->travel_charge > 0): ?> · Travel ₹<?php echo e(number_format($location->travel_charge, 0)); ?> <?php endif; ?></p>
                                </div>
                            </label>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="card p-6">
                    <h2 class="text-lg font-semibold text-slate-900">4. Date, time & duration</h2>
                    <div class="mt-4 grid gap-4 sm:grid-cols-3">
                        <div>
                            <label class="label">Start time</label>
                            <input type="datetime-local" name="start_at" class="input" required value="<?php echo e(old('start_at')); ?>">
                        </div>
                        <div>
                            <label class="label">Duration (minutes)</label>
                            <input type="number" name="duration_minutes" class="input" min="15" max="600" step="15" required value="<?php echo e(old('duration_minutes', 60)); ?>">
                        </div>
                        <div>
                            <label class="label">Discount code (optional)</label>
                            <input type="text" name="discount_code" class="input" value="<?php echo e(old('discount_code')); ?>">
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="label">Notes (optional)</label>
                        <textarea name="notes" rows="3" class="input"><?php echo e(old('notes')); ?></textarea>
                    </div>
                </div>

                <button type="submit" class="btn-primary">Continue to checkout</button>
            </form>
        </div>

        <div class="space-y-6">
            <div class="card p-6">
                <h2 class="text-lg font-semibold text-slate-900">Available slots</h2>
                <p class="mt-1 text-sm text-slate-500"><?php echo e($date->format('d M Y')); ?></p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <?php $__empty_1 = true; $__currentLoopData = $slots; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $slot): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <span class="badge <?php echo e($slot['status'] === 'available' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'); ?>"
    data-start-time="<?php echo e($slot['start_time']); ?>"
    data-duration="<?php echo e($slot['duration_minutes']); ?>"
    onclick="fillSlot(this)">
    <?php echo e($slot['start_time']); ?> - <?php echo e($slot['end_time']); ?> (<?php echo e(ucfirst($slot['status'])); ?>)</span>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <p class="text-sm text-slate-500">No slots available</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function fillSlot(element) {
    const startTime = element.dataset.startTime;
    const duration = element.dataset.duration;
    
    // Fill start_at datetime-local input
    const startInput = document.querySelector('input[name="start_at"]');
    if (startInput) {
        const dateInput = document.querySelector('input[name="date"]');
        const datePart = dateInput ? dateInput.value.split('T')[0] : new Date().toISOString().split('T')[0];
        startInput.value = datePart + 'T' + startTime;
    }
    
    // Fill duration_minutes input
    const durationInput = document.querySelector('input[name="duration_minutes"]');
    if (durationInput) {
        durationInput.value = duration;
    }
    
    // Auto-submit the form to proceed to checkout
    document.querySelector('button[type="submit"]').click();
}
</script>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Teacherapp\resources\views/bookings/create.blade.php ENDPATH**/ ?>