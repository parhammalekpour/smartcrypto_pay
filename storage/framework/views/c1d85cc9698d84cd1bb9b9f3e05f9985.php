

<?php $__env->startSection('title', __('user.send_crypto_title') . ' - CryptoPay'); ?>
<?php $__env->startSection('page-title', __('user.send_crypto_title')); ?>
<?php $__env->startSection('page-subtitle', __('user.send_crypto_description')); ?>

<?php $__env->startSection('content'); ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Transfer Form -->
    <div class="lg:col-span-2 bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-6 pb-4 border-b border-gray-200"><?php echo e(__('user.send_crypto_title')); ?></h3>

        <form method="POST" action="<?php echo e(route('user.send.post')); ?>" class="space-y-6">
            <?php echo csrf_field(); ?>

            <!-- Select Wallet to Send From -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2"><?php echo e(__('user.wallet_source')); ?></label>
                <select name="sender_wallet_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                    <option value=""><?php echo e(__('user.select_wallet')); ?></option>
                    <?php if($wallets && $wallets->count() > 0): ?>
                        <?php $__currentLoopData = $wallets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $wallet): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($wallet->id); ?>" <?php if(isset($preselected) && $preselected == $wallet->id): ?> selected <?php endif; ?>>
                                <?php echo e($wallet->currency); ?> - <?php echo e(\App\Support\NumberHelper::formatCryptoAmount($wallet->balance)); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php endif; ?>
                </select>
                <?php $__errorArgs = ['sender_wallet_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <p class="text-red-600 text-xs mt-1"><?php echo e($message); ?></p>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <!-- Recipient Wallet Address -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2"><?php echo e(__('user.recipient_address')); ?></label>
                                <input type="text" name="wallet_address" required placeholder="<?php echo e(__('user.recipient_address_placeholder')); ?>" 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                <?php $__errorArgs = ['wallet_address'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <p class="text-red-600 text-xs mt-1"><?php echo e($message); ?></p>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <!-- Amount -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2"><?php echo e(__('common.amount')); ?></label>
                <input type="text" name="amount" required placeholder="0.001" 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                <?php $__errorArgs = ['amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <p class="text-red-600 text-xs mt-1"><?php echo e($message); ?></p>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <?php
                $two = \App\Models\TwoFactor::where('user_id', auth()->id())->first();
            ?>

            <?php if(!$two || !$two->enabled_at): ?>
                <div class="mb-4 p-4 rounded-lg bg-yellow-50 border border-yellow-200 text-yellow-700 text-sm">
                                    <?php echo e(__('user.two_factor_hint')); ?>

                </div>
            <?php endif; ?>

            <?php if($two && $two->enabled_at): ?>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2"><?php echo e(__('user.two_factor_code')); ?></label>
                                        <input type="text" name="two_factor_token" required placeholder="<?php echo e(__('user.two_factor_code_placeholder')); ?>"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <?php $__errorArgs = ['two_factor_token'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="text-red-600 text-xs mt-1"><?php echo e($message); ?></p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
            <?php endif; ?>

            <!-- Description (Optional) -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2"><?php echo e(__('user.description_optional')); ?></label>
                                <textarea name="description" placeholder="<?php echo e(__('user.description_placeholder')); ?>" rows="3"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"></textarea>
            </div>

            <!-- Submit -->
            <div class="flex gap-3">
                <button type="submit" class="flex-1 bg-indigo-600 text-white py-2 rounded-lg font-semibold hover:bg-indigo-700 transition" <?php if(!$two || !$two->enabled_at): ?> disabled <?php endif; ?>>
                    <i class="fas fa-paper-plane ml-2"></i><?php echo e(__('common.send')); ?>

                </button>
                <a href="<?php echo e(route('user.dashboard')); ?>" class="flex-1 bg-gray-200 text-gray-800 py-2 rounded-lg font-semibold hover:bg-gray-300 transition text-center">
                    <?php echo e(__('common.cancel')); ?>

                </a>
            </div>
        </form>
    </div>

    <!-- Info Card -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
        <div class="flex gap-3 mb-4">
            <i class="fas fa-info-circle text-blue-600 text-lg mt-1"></i>
            <div>
                <h4 class="font-semibold text-gray-800"><?php echo e(__('user.important_notes')); ?></h4>
            </div>
        </div>
        
        <ul class="space-y-3 text-sm text-gray-700">
            <li class="flex gap-2">
                <span class="text-blue-600">•</span>
                <span><?php echo e(__('user.transfer_note_1')); ?></span>
            </li>
            <li class="flex gap-2">
                <span class="text-blue-600">•</span>
                <span><?php echo e(__('user.transfer_note_2')); ?></span>
            </li>
            <li class="flex gap-2">
                <span class="text-blue-600">•</span>
                <span><?php echo e(__('user.transfer_note_3')); ?></span>
            </li>
            <li class="flex gap-2">
                <span class="text-blue-600">•</span>
                <span><?php echo e(__('user.transfer_note_4')); ?></span>
            </li>
        </ul>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.dashboard', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\smart-cryptopay\resources\views/user/send.blade.php ENDPATH**/ ?>