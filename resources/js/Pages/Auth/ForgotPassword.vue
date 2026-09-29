<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import axios from 'axios';

const step = ref(1); // 1: Request OTP via Phone, 2: Enter OTP & New Password
const phone = ref('');
const otp = ref('');
const password = ref('');
const passwordConfirmation = ref('');
const loading = ref(false);
const errorMsg = ref('');
const successMsg = ref('');

const sendResetOtp = async () => {
    loading.value = true;
    errorMsg.value = '';
    successMsg.value = '';
    try {
        const response = await axios.post(route('auth.phone-forgot-password'), {
            phone: phone.value,
        });

        loading.value = false;
        if (response.data.success) {
            successMsg.value = response.data.message;
            if (response.data.otp) {
                otp.value = response.data.otp;
            }
            step.value = 2;
        }
    } catch (err) {
        loading.value = false;
        errorMsg.value = err.response?.data?.message || 'Failed to send recovery OTP. Please check your phone number.';
    }
};

const resetPassword = async () => {
    if (password.value !== passwordConfirmation.value) {
        errorMsg.value = 'Passwords do not match.';
        return;
    }

    loading.value = true;
    errorMsg.value = '';
    successMsg.value = '';
    try {
        const response = await axios.post(route('auth.phone-reset-password'), {
            phone: phone.value,
            otp: otp.value,
            password: password.value,
            password_confirmation: passwordConfirmation.value,
        });

        loading.value = false;
        if (response.data.success) {
            successMsg.value = response.data.message;
            setTimeout(() => {
                router.visit(route('consumer.catalog'));
            }, 1500);
        }
    } catch (err) {
        loading.value = false;
        errorMsg.value = err.response?.data?.message || 'Password reset failed. Please verify the OTP.';
    }
};
</script>

<template>
    <GuestLayout>
        <Head title="Forgot Password" />

        <div class="mb-4 text-sm text-zinc-400">
            Forgot your password? Enter your registered phone number below and we will send a 6-digit OTP code via SMS to reset your password.
        </div>

        <div v-if="successMsg" class="mb-4 rounded-xl bg-emerald-950/60 border border-emerald-500/40 p-3 text-xs text-emerald-300 font-semibold">
            {{ successMsg }}
        </div>

        <div v-if="errorMsg" class="mb-4 rounded-xl bg-red-950/60 border border-red-500/40 p-3 text-xs text-red-300 font-semibold">
            {{ errorMsg }}
        </div>

        <!-- Step 1: Send Phone OTP -->
        <form v-if="step === 1" @submit.prevent="sendResetOtp" class="space-y-4">
            <div>
                <InputLabel for="phone" value="Phone Number" />

                <TextInput
                    id="phone"
                    type="tel"
                    class="mt-1 block w-full"
                    v-model="phone"
                    placeholder="09031704109"
                    required
                    autofocus
                />
            </div>

            <div class="flex items-center justify-between pt-2">
                <Link :href="route('consumer.catalog')" class="text-xs text-zinc-400 hover:text-white underline">
                    Back to Catalog
                </Link>

                <PrimaryButton :class="{ 'opacity-25': loading }" :disabled="loading">
                    {{ loading ? 'Sending OTP...' : 'Send Recovery OTP' }}
                </PrimaryButton>
            </div>
        </form>

        <!-- Step 2: Enter OTP & New Password -->
        <form v-else @submit.prevent="resetPassword" class="space-y-4">
            <div>
                <InputLabel for="otp" value="6-Digit Recovery OTP" />
                <TextInput
                    id="otp"
                    type="text"
                    maxlength="6"
                    class="mt-1 block w-full text-center font-mono text-lg text-amber-400 tracking-widest"
                    v-model="otp"
                    placeholder="123456"
                    required
                    autofocus
                />
            </div>

            <div>
                <InputLabel for="password" value="New Password" />
                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="password"
                    placeholder="Minimum 6 characters"
                    required
                    minlength="6"
                />
            </div>

            <div>
                <InputLabel for="password_confirmation" value="Confirm New Password" />
                <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="passwordConfirmation"
                    placeholder="Repeat new password"
                    required
                    minlength="6"
                />
            </div>

            <div class="flex items-center justify-between pt-2">
                <button
                    type="button"
                    @click="sendResetOtp"
                    :disabled="loading"
                    class="text-xs text-amber-400 hover:text-amber-300 underline"
                >
                    Resend OTP
                </button>

                <PrimaryButton :class="{ 'opacity-25': loading }" :disabled="loading">
                    {{ loading ? 'Resetting Password...' : 'Reset Password & Login' }}
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
