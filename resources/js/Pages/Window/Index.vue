<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import {Head, useForm, usePage} from '@inertiajs/vue3';
import PrimaryButton from "@/Components/PrimaryButton.vue";
import {ref} from "vue";

const form = useForm({});

const start = () => {
    form.post(route('window.start'));
};

const moveForm = useForm({
    position: '',
    step: 0,
});
const move = (position, step) => {
    moveForm.position = position;
    moveForm.step = step;
    moveForm.post(route('window.move'));
};

Echo.channel(`test-${usePage().props.auth.user.id}`)
    .listen('TestEvent', (e) => {
        const newDiv = document.createElement('div');
        const content = document.createTextNode(`[${e.time}]: ${e.message}`);
        newDiv.appendChild(content);
        document.querySelector('#logs').prepend(newDiv);
    });

</script>

<template>
    <Head title="Window" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
        </template>
        <div class="py-6">
            <div class="mx-auto sm:px-6 lg:px-8" style="width: 1500px">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="w-100 border mb-2 flex flex-row items-center" style="height: 50px">
                            <PrimaryButton :disabled="!$page.props.targets.length" :class="{'opacity-25': !$page.props.targets.length}">
                                Fight{{ $page.props.targets.length ? ` (${$page.props.targets.length})` : '' }}
                            </PrimaryButton>
                        </div>
                        <div class="text-center flex flex-row">
                            <div class="inline-block border" style="width: 1002px"
                                 @keyup.up="move('y', -1)"
                                 @keyup.down="move('y', 1)"
                                 @keyup.right="move('x', 1)"
                                 @keyup.left="move('x', -1)"
                                 tabindex="0"
                            >
                                <div v-for="row in $page.props.map" class="flex flex-row">
                                    <div v-for="cell in row" class="flex items-center justify-center" style="width: 100px; height: 100px">
                                        <div v-if="$page.props.position.x === cell.x && $page.props.position.y === cell.y"
                                             class="bg-slate-200 flex flex-row items-center justify-center"
                                             style="width: 100px; height: 100px"
                                        >
                                            Player
                                        </div>
                                        <div v-else class="flex flex-row items-center justify-center"
                                             :class="{'bg-green-200': cell.wood}"
                                             style="width: 100px; height: 100px"
                                        >
                                            {{ cell.y }}x{{ cell.x }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="logs" class="ml-2 border text-left" style="width: 100%; max-height: 1002px; overflow-y: scroll;"></div>
                        </div>
                        <PrimaryButton
                            :class="{'opacity-25': form.processing}"
                            :disabled="form.processing"
                            @click="start"
                        >
                            Start {{ $page.props.test }}
                        </PrimaryButton>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
