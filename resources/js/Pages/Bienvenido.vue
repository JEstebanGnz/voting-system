<template>
    <GeneralLayout>
        <template v-slot:app-bar-content>
            <v-btn text class="white--text" :href="route('login')" v-if="!isLogged">
                Iniciar sesión
            </v-btn>
            <v-btn text class="white--text" :href="route('test')" v-if="isLogged">
                Ir al panel de administración
            </v-btn>
        </template>

    </GeneralLayout>
</template>

<script>
import GeneralLayout from "@/Layouts/GeneralLayout";
import {Link} from '@inertiajs/inertia-vue';

export default {
    components: {
        Link,
        GeneralLayout,
    },

    data: () => ({
        isLogged: false
    }),

    created() {
        this.isLogged = this.$page.props.user.email !== undefined;
    }
}
</script>
<style>
.active-button {
    color: #FAFAFA !important;
}
</style>
