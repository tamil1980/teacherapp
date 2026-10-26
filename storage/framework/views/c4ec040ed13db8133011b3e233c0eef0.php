<footer class="mt-16 border-t border-slate-200 bg-white">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-600 text-lg font-black text-white">E</span>
                    <span class="text-xl font-extrabold tracking-tight text-slate-900"><?php echo e($platformName ?? 'EduBook'); ?></span>
                </div>
                <p class="mt-3 text-sm leading-relaxed text-slate-500">
                    India's tuition marketplace for verified teachers across State Board, Matriculation, CBSE and ICSE.
                    Book online or at-home classes at transparent hourly rates.
                </p>
                <p class="mt-3 text-sm text-slate-500">
                    Support: <a class="font-medium text-brand-600 hover:underline" href="tel:<?php echo e($supportPhone ?? ''); ?>"><?php echo e($supportPhone ?? ''); ?></a>
                    <span class="mx-1 text-slate-300">·</span>
                    <a class="font-medium text-brand-600 hover:underline" href="mailto:<?php echo e($supportEmail ?? ''); ?>"><?php echo e($supportEmail ?? ''); ?></a>
                </p>
            </div>

            <div>
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-900">For Parents</h3>
                <ul class="mt-3 space-y-2 text-sm">
                    <li><a href="<?php echo e(route('teachers.index')); ?>" class="text-slate-500 hover:text-brand-600">Browse teachers</a></li>
                    <li><a href="<?php echo e(route('subjects.index')); ?>" class="text-slate-500 hover:text-brand-600">Subjects</a></li>
                    <li><a href="<?php echo e(route('boards.index')); ?>" class="text-slate-500 hover:text-brand-600">School boards</a></li>
                    <li><a href="<?php echo e(route('classes.index')); ?>" class="text-slate-500 hover:text-brand-600">Class standards</a></li>
                    <li><a href="<?php echo e(route('register.parent')); ?>" class="text-slate-500 hover:text-brand-600">Create an account</a></li>
                </ul>
            </div>

            <div>
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-900">For Teachers</h3>
                <ul class="mt-3 space-y-2 text-sm">
                    <li><a href="<?php echo e(route('register.teacher')); ?>" class="text-slate-500 hover:text-brand-600">Teach on <?php echo e($platformName ?? 'EduBook'); ?></a></li>
                    <li><a href="<?php echo e(route('teacher.dashboard')); ?>" class="text-slate-500 hover:text-brand-600">Teacher dashboard</a></li>
                    <li><a href="<?php echo e(route('teacher.fees.index')); ?>" class="text-slate-500 hover:text-brand-600">Manage teaching fees</a></li>
                    <li><a href="<?php echo e(route('teacher.availability.index')); ?>" class="text-slate-500 hover:text-brand-600">Set availability</a></li>
                    <li><a href="<?php echo e(route('teacher.earnings.index')); ?>" class="text-slate-500 hover:text-brand-600">Earnings & payouts</a></li>
                </ul>
            </div>

            <div>
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-900">Company</h3>
                <ul class="mt-3 space-y-2 text-sm">
                    <li><a href="<?php echo e(route('about')); ?>" class="text-slate-500 hover:text-brand-600">About us</a></li>
                    <li><a href="<?php echo e(route('contact')); ?>" class="text-slate-500 hover:text-brand-600">Contact</a></li>
                    <li><a href="<?php echo e(route('faq')); ?>" class="text-slate-500 hover:text-brand-600">FAQ</a></li>
                    <li><a href="<?php echo e(route('terms')); ?>" class="text-slate-500 hover:text-brand-600">Terms & conditions</a></li>
                    <li><a href="<?php echo e(route('privacy')); ?>" class="text-slate-500 hover:text-brand-600">Privacy policy</a></li>
                    <li><a href="<?php echo e(route('cancellation-policy')); ?>" class="text-slate-500 hover:text-brand-600">Cancellation & refunds</a></li>
                </ul>
            </div>
        </div>

        <div class="mt-10 border-t border-slate-200 pt-6 text-xs text-slate-400">
            © <?php echo e(date('Y')); ?> <?php echo e($platformName ?? 'EduBook'); ?>. All rights reserved.
            <span class="ml-2">Prices shown in Indian Rupees (INR). Sessions scheduled in Asia/Kolkata time.</span>
        </div>
    </div>
</footer>
<?php /**PATH D:\Teacherapp\resources\views/partials/footer.blade.php ENDPATH**/ ?>