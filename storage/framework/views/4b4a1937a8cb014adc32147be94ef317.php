<?php
    $navLinks = [
        ['href' => route('home'), 'label' => 'Home', 'active' => request()->routeIs('home')],
        ['href' => route('teachers.index'), 'label' => 'Find teachers', 'active' => request()->routeIs('teachers.*')],
        ['href' => route('subjects.index'), 'label' => 'Subjects', 'active' => request()->routeIs('subjects.*')],
        ['href' => route('boards.index'), 'label' => 'School boards', 'active' => request()->routeIs('boards.*')],
        ['href' => route('home').'#how-it-works', 'label' => 'How it works', 'active' => false],
    ];
?>

<header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/95 backdrop-blur">
    <div class="mx-auto flex min-h-16 max-w-7xl items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
        <div class="flex min-w-0 items-center gap-5">
            <a href="<?php echo e(route('home')); ?>" class="flex shrink-0 items-center gap-2.5" aria-label="<?php echo e($platformName ?? 'EduBook'); ?> home">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-600 text-lg font-black text-white shadow-sm">E</span>
                <span class="brand-wordmark text-lg font-extrabold tracking-tight text-slate-900 sm:text-xl"><?php echo e($platformName ?? 'EduBook'); ?></span>
            </a>

            <nav aria-label="Main navigation" class="hidden items-center gap-0.5 lg:flex">
                <?php $__currentLoopData = $navLinks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $link): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <a href="<?php echo e($link['href']); ?>"
                       <?php if($link['active']): ?> aria-current="page" <?php endif; ?>
                       class="whitespace-nowrap rounded-lg px-2.5 py-2 text-sm font-medium transition hover:bg-slate-100 hover:text-slate-900 <?php echo e($link['active'] ? 'bg-slate-100 text-slate-900' : 'text-slate-600'); ?>">
                        <?php echo e($link['label']); ?>

                    </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php if(auth()->guard()->guest()): ?>
                    <a href="<?php echo e(route('register.teacher')); ?>" class="whitespace-nowrap rounded-lg px-2.5 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">Become a teacher</a>
                <?php endif; ?>
            </nav>
        </div>

        <div class="flex shrink-0 items-center gap-1.5 sm:gap-2">
            <?php if(auth()->guard()->check()): ?>
                <?php if(auth()->user()->role === 'parent'): ?>
                    <a href="<?php echo e(route('parent.dashboard')); ?>" class="btn-ghost hidden sm:inline-flex">Dashboard</a>
                <?php elseif(auth()->user()->role === 'teacher'): ?>
                    <a href="<?php echo e(route('teacher.dashboard')); ?>" class="btn-ghost hidden sm:inline-flex">Dashboard</a>
                <?php elseif(auth()->user()->role === 'admin'): ?>
                    <a href="<?php echo e(route('admin.dashboard')); ?>" class="btn-ghost hidden sm:inline-flex">Admin panel</a>
                <?php endif; ?>

                <details class="group relative">
                    <summary class="flex cursor-pointer list-none items-center gap-2 rounded-lg px-2 py-1.5 transition hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-100 text-sm font-bold text-brand-700">
                            <?php echo e(strtoupper(substr(auth()->user()->name, 0, 1))); ?>

                        </span>
                        <span class="hidden text-sm font-medium text-slate-700 sm:block"><?php echo e(Str::before(auth()->user()->name, ' ')); ?></span>
                        <svg class="h-4 w-4 text-slate-400 transition group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </summary>
                    <div class="absolute right-0 z-50 mt-2 w-60 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl">
                        <div class="border-b border-slate-100 px-4 py-3">
                            <p class="truncate text-sm font-semibold text-slate-900"><?php echo e(auth()->user()->name); ?></p>
                            <p class="truncate text-xs text-slate-500"><?php echo e(auth()->user()->email); ?></p>
                        </div>
                        <div class="py-1">
                            <?php if(auth()->user()->role === 'parent'): ?>
                                <a href="<?php echo e(route('parent.bookings.index')); ?>" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">My Bookings</a>
                                <a href="<?php echo e(route('parent.students.index')); ?>" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">My Children</a>
                                <a href="<?php echo e(route('parent.payments.index')); ?>" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Payments & Refunds</a>
                                <a href="<?php echo e(route('notifications.index')); ?>" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Notifications</a>
                            <?php elseif(auth()->user()->role === 'teacher'): ?>
                                <a href="<?php echo e(route('teacher.bookings.index')); ?>" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">My Bookings</a>
                                <a href="<?php echo e(route('teacher.availability.index')); ?>" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Availability</a>
                                <a href="<?php echo e(route('teacher.fees.index')); ?>" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Manage Fees</a>
                                <a href="<?php echo e(route('teacher.earnings.index')); ?>" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Earnings</a>
                            <?php else: ?>
                                <a href="<?php echo e(route('admin.teachers.index')); ?>" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Teachers</a>
                                <a href="<?php echo e(route('admin.bookings.index')); ?>" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Bookings</a>
                                <a href="<?php echo e(route('admin.payments.index')); ?>" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Payments</a>
                                <a href="<?php echo e(route('admin.payouts.index')); ?>" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Payouts</a>
                                <a href="<?php echo e(route('admin.settings.index')); ?>" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Settings</a>
                            <?php endif; ?>
                            <a href="<?php echo e(route('support.create')); ?>" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Help & Support</a>
                        </div>
                        <div class="border-t border-slate-100 py-1">
                            <form method="POST" action="<?php echo e(route('logout')); ?>">
                                <?php echo csrf_field(); ?>
                                <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-rose-600 hover:bg-rose-50">Sign out</button>
                            </form>
                        </div>
                    </div>
                </details>
            <?php else: ?>
                <a href="<?php echo e(route('login')); ?>" class="btn-ghost hidden sm:inline-flex">Log in</a>
                <a href="<?php echo e(route('register.parent')); ?>" class="btn-secondary hidden sm:inline-flex">Register</a>
                <a href="<?php echo e(route('teachers.index')); ?>" class="btn-primary px-3 sm:px-4">Find a teacher</a>
            <?php endif; ?>

            <details class="group relative lg:hidden">
                <summary class="flex h-10 w-10 cursor-pointer list-none items-center justify-center rounded-lg border border-slate-200 text-slate-700 transition hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500" aria-label="Open navigation menu">
                    <svg class="h-5 w-5 group-open:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    <svg class="hidden h-5 w-5 group-open:block" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </summary>
                <nav aria-label="Mobile navigation" class="absolute right-0 z-50 mt-2 w-[min(20rem,calc(100vw-2rem))] rounded-xl border border-slate-200 bg-white p-2 shadow-xl">
                    <?php $__currentLoopData = $navLinks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $link): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <a href="<?php echo e($link['href']); ?>" class="block rounded-lg px-3 py-2.5 text-sm font-medium <?php echo e($link['active'] ? 'bg-brand-50 text-brand-700' : 'text-slate-700 hover:bg-slate-50'); ?>"><?php echo e($link['label']); ?></a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php if(auth()->guard()->guest()): ?>
                        <a href="<?php echo e(route('register.teacher')); ?>" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Become a teacher</a>
                        <a href="<?php echo e(route('login')); ?>" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 sm:hidden">Log in</a>
                        <a href="<?php echo e(route('register.parent')); ?>" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 sm:hidden">Register as a parent</a>
                    <?php endif; ?>
                </nav>
            </details>
        </div>
    </div>
</header>
<?php /**PATH D:\Teacherapp\resources\views/partials/navbar.blade.php ENDPATH**/ ?>