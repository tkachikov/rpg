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

const battleForm = useForm({});

const startBattle = () => {
    battleForm.post(route('window.battle'));
};

const leaveBattle = () => {
    useForm({}).post(route('window.leaveBattle'));
};

const fight = () => {
    useForm({}).post(route('window.fight'));
};

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
                            <PrimaryButton :disabled="!$page.props.targets.length" :class="{'opacity-25': !$page.props.targets.length}" @click="startBattle">
                                Fight{{ $page.props.targets.length ? ` (${$page.props.targets.length})` : '' }}
                            </PrimaryButton>
                            <PrimaryButton :disabled="!$page.props.battleStatus" @click="leaveBattle" class="ml-3" :class="{'opacity-25': !$page.props.battleStatus}">
                                Leave
                            </PrimaryButton>
                        </div>
                        <div class="text-center flex flex-row">
                            <div v-if="!$page.props.battleStatus" class="inline-block border" style="width: 1002px"
                                 @keyup.up="move('y', -1)"
                                 @keyup.down="move('y', 1)"
                                 @keyup.right="move('x', 1)"
                                 @keyup.left="move('x', -1)"
                                 tabindex="0"
                            >
                                <div v-for="row in $page.props.map" class="flex flex-row">
                                    <div v-for="cell in row" class="flex items-center justify-center" style="width: 100px; height: 100px">
                                        <div v-if="$page.props.position.x === cell.x && $page.props.position.y === cell.y"
                                             class="flex flex-col bg-slate-200"
                                             style="width: 100px; height: 100px"
                                        >
                                            <div class="flex flex-row" style="height: 25px;">
                                                <div class="w-1/5"></div>
                                                <div class="w-3/5" style="rotate: -90deg" :class="{'text-slate-300': $page.props.moveName !== 'up'}">&#10093;</div>
                                                <div class="w-1/5"></div>
                                            </div>
                                            <div class="flex flex-row items-center" style="height: 50px;">
                                                <div class="w-1/5" :class="{'text-slate-300': $page.props.moveName !== 'left'}">&#10092;</div>
                                                <div class="w-3/5">Player</div>
                                                <div class="w-1/5" :class="{'text-slate-300': $page.props.moveName !== 'right'}">&#10093;</div>
                                            </div>
                                            <div class="flex flex-row" style="height: 25px;">
                                                <div class="w-1/5"></div>
                                                <div class="w-3/5" style="rotate: 90deg" :class="{'text-slate-300': $page.props.moveName !== 'down'}">&#10093;</div>
                                                <div class="w-1/5"></div>
                                            </div>
                                        </div>
                                        <div v-else class="flex flex-row items-center justify-center" :class="[cell.color]" style="width: 100px; height: 100px">

                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="inline-block border" style="width: 1002px;" tabindex="0">
                                <div style="width: 1002px; height: 1002px;">
                                    <div class="flex flex-row" style="height: 501px;">
                                        <div class="w-1/3 m-2 border">
                                            <div style="height: 101px;" class="flex flex-row items-center justify-center">
                                                Player
                                            </div>
                                            <div style="height: calc(400px - 99px - 2rem)">
                                                items
                                            </div>
                                            <div>
                                                <div class="m-2 border relative">
                                                    <div class="bg-red-300 absolute" :style="{'height': '24px', 'width': 'calc(100% * '+($page.props.player.health / $page.props.player.fullHealth)+')'}"></div>
                                                    <div class="relative">{{ `${$page.props.player.health} / ${$page.props.player.fullHealth}` }}</div>
                                                </div>
                                                <div class="m-2 border">
                                                    <div class="bg-blue-300">mana</div>
                                                </div>
                                                <div class="m-2 border">
                                                    <div class="bg-yellow-300">else</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="w-1/3 m-2 border">
                                            <div class="flex flex-row items-center justify-center h-full">
                                                <PrimaryButton @click="fight">Fight</PrimaryButton>
                                            </div>
                                        </div>
                                        <div class="w-1/3 m-2 border">
                                            <div style="height: 101px;" class="flex flex-row items-center justify-center">
                                                Target
                                            </div>
                                            <div style="height: calc(400px - 99px - 2rem)">
                                                items
                                            </div>
                                            <div>
                                                <div class="m-2 border relative">
                                                    <div class="bg-red-300 absolute" :style="{'height': '24px', 'width': 'calc(100% * '+($page.props.targetFight.health / $page.props.targetFight.fullHealth)+')'}"></div>
                                                    <div class="relative">{{ `${$page.props.targetFight.health} / ${$page.props.targetFight.fullHealth}` }}</div>
                                                </div>
                                                <div class="m-2 border">
                                                    <div class="bg-blue-300">mana</div>
                                                </div>
                                                <div class="m-2 border">
                                                    <div class="bg-yellow-300">else</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div></div>
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
