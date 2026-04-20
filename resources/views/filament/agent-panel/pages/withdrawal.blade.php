<x-filament-panels::page>
    @if($this->phase === 'initiate')
        <form wire:submit="initiateWithdrawal">
            {{ $this->form }}

            @if($this->commissionAmount !== null)
                <div class="mt-4 p-4 rounded-lg bg-warning-50 dark:bg-warning-950 border border-warning-300 dark:border-warning-700">
                    <p class="text-sm font-medium text-warning-800 dark:text-warning-200">
                        العمولة: <strong>{{ number_format($this->commissionAmount, 2) }} YER</strong>
                    </p>
                    <p class="text-sm font-medium text-warning-800 dark:text-warning-200">
                        إجمالي المبلغ المخصوم: <strong>{{ number_format($this->totalDeducted, 2) }} YER</strong>
                    </p>
                </div>
            @endif

            <div class="mt-4">
                <x-filament::button type="submit">
                    بدء عملية السحب
                </x-filament::button>
            </div>
        </form>
    @elseif($this->phase === 'verify')
        <div class="space-y-6">
            <div class="p-4 rounded-lg bg-info-50 dark:bg-info-950 border border-info-300 dark:border-info-700">
                <h3 class="text-lg font-bold text-info-800 dark:text-info-200 mb-2">في انتظار التأكيد</h3>
                <p class="text-sm text-info-700 dark:text-info-300">
                    تم إرسال رمز التحقق إلى العميل. يرجى إدخال الرمز أدناه.
                </p>
                <div class="mt-2 text-sm text-info-700 dark:text-info-300">
                    <p>العمولة: <strong>{{ number_format($this->commissionAmount, 2) }} YER</strong></p>
                    <p>الإجمالي المخصوم: <strong>{{ number_format($this->totalDeducted, 2) }} YER</strong></p>
                </div>
            </div>

            <div class="mt-4">
                <label for="otp" class="fi-fo-field-wrp-label inline-flex items-center gap-x-3 mb-2">
                    <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">رمز التحقق</span>
                    <sup class="text-danger-600 dark:text-danger-400 font-medium"></sup>
                </label>
                
                <x-filament::input.wrapper>
                    <x-filament::input
                        type="text"
                        id="otp"
                        wire:model="otpCode"
                        maxlength="6"
                        placeholder="------"
                        dir="ltr"
                        class="text-center tracking-[0.5em] font-mono"
                    />
                </x-filament::input.wrapper>
                
                <p class="fi-fo-field-wrp-helper-text mt-2 text-sm text-gray-500 dark:text-gray-400">
                    أدخل رمز التحقق المكون من 6 أرقام المرسل إلى هاتف العميل.
                </p>
            </div>

            <div class="flex gap-3">
                <x-filament::button wire:click="verifyOtp" color="success">
                    تأكيد السحب
                </x-filament::button>
                <x-filament::button wire:click="cancelWithdrawal" color="danger" outlined>
                    إلغاء العملية
                </x-filament::button>
            </div>
        </div>
    @endif
</x-filament-panels::page>
