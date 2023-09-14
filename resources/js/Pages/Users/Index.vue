<template>
    <AuthenticatedLayout>
        <!--Snackbars-->
        <v-snackbar
            v-model="snackbar.status"
            :timeout="snackbar.timeout"
            color="red accent-2"
            top
            right
        >
            {{ snackbar.text }}

        </v-snackbar>

        <v-container>
            <div class="dd-flex flex-column align-end mb-8">
                <h2 class="align-self-start">Gestionar usuarios</h2>
            </div>
            <v-card>
                <v-card-title>
                    <v-text-field
                        v-model="search"
                        append-icon="mdi-magnify"
                        label="Filtrar por nombre"
                        single-line
                        hide-details
                    ></v-text-field>
                </v-card-title>
                <!--Inicia tabla-->
                <v-data-table
                    :search="search"
                    loading-text="Cargando, por favor espere..."
                    :loading="isLoading"
                    :headers="headers"
                    :items="users"
                    :items-per-page="15"
                    class="elevation-1"
                >
                    <template v-slot:item.has_payment="{item}">
                        {{ item.has_payment === 1 ? 'Sí' : 'No' }}
                    </template>
                </v-data-table>
                <!--Acaba tabla-->
            </v-card>
        </v-container>
    </AuthenticatedLayout>
</template>

<script>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import {InertiaLink} from "@inertiajs/inertia-vue";
import {prepareErrorText} from "@/HelperFunctions"
import ConfirmDialog from "@/Components/ConfirmDialog";

export default {
    components: {
        ConfirmDialog,
        AuthenticatedLayout,
        InertiaLink,
    },
    data: () => {
        return {
            //Table info
            headers: [
                {text: 'Nombre', value: 'name'},
                {text: 'Correo electrónico', value: 'email'},
                {text: 'C.C', value: 'identification_number'},
                {text: 'Pagó?', value: 'has_payment'},
            ],
            users: [],
            //Snackbars
            snackbar: {
                text: '...',
                status: false,
                timeout: 3000
            },
            //overlays
            isLoading: true,
            search: '',
        }
    },
    async created() {
        await this.getAllUsers();
        this.isLoading = false;
    },
    methods: {

        getAllUsers: async function () {
            let request = await axios.get(route('api.users.index'));
            this.users = request.data;
        },

    },


}
</script>
