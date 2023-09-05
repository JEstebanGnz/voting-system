<template>
    <AuthenticatedLayout>

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
            <div class="d-flex flex-column align-end mb-8">
                <h2 class="align-self-start">Gestionar roles</h2>
                <div>
                    <v-btn
                        color="primario"
                        class="grey--text text--lighten-4"
                        @click="createRoleDialog = true"
                    >
                        Crear nuevo rol
                    </v-btn>
                </div>

            </div>

            <!--Inicia tabla-->
            <v-data-table
                loading-text="Cargando, por favor espere..."
                :loading="isLoading"
                :headers="headers"
                :items="roles"
                :items-per-page="5"
                class="elevation-1"
            >
                <template v-slot:item.actions="{ item }">
                    <v-icon
                        class="mr-2 primario--text"
                        @click="openEditRoleModal(item)"
                    >
                        mdi-pencil
                    </v-icon>
                    <v-icon
                        class="primario--text"
                        @click="confirmDeleteRole(item)"
                    >
                        mdi-delete
                    </v-icon>
                </template>
            </v-data-table>
            <!--Acaba tabla-->

            <!------------Seccion de dialogos ---------->
            <!--Crear rol -->
            <v-dialog
                v-model="createRoleDialog"
                persistent
                max-width="600px"
            >

                <v-card>
                    <v-card-title>
                        <span class="text-h5">Crear un nuevo rol</span>
                    </v-card-title>
                    <v-card-text>
                        <v-container>
                            <v-row>
                                <v-col cols="12">
                                    <v-text-field
                                        label="Nombre del rol *"
                                        required
                                        v-model="newRole.name"
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="12">
                                    <v-text-field
                                        label="ID del rol *"
                                        required
                                        v-model="newRole.id"
                                    ></v-text-field>
                                </v-col>

                            </v-row>
                        </v-container>
                        <small>Los campos con * son obligatorios</small>
                    </v-card-text>
                    <v-card-actions>
                        <v-spacer></v-spacer>
                        <v-btn
                            color="primario"
                            text
                            @click="createRoleDialog = false"
                        >
                            Cancelar
                        </v-btn>
                        <v-btn
                            color="primario"
                            text
                            @click="createRole"
                        >
                            Guardar cambios
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>
            <!--Confirmar borrar rol-->
            <confirm-dialog
                :show="deleteRoleDialog"
                @canceled-dialog="deleteRoleDialog = false"
                @confirmed-dialog="deleteRole(deletedRoleId)"
            >
                <template v-slot:title>
                    Estas a punto de eliminar el rol seleccionado
                </template>

                ¡Cuidado! esta acción es irreversible

                <template v-slot:confirm-button-text>
                    Borrar
                </template>
            </confirm-dialog>

            <!--Editar role-->
            <v-dialog
                v-model="editRoleDialog"
                persistent
                max-width="600px"
            >
                <v-card>
                    <v-card-title>
                        <span class="text-h5">Editar rol</span>
                    </v-card-title>
                    <v-card-text>
                        <v-container>
                            <v-row>
                                <v-col cols="12">
                                    <v-text-field
                                        label="Nombre del rol *"
                                        required
                                        v-model="editedRole.name"
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="12">
                                    <v-text-field
                                        label="ID del rol *"
                                        required
                                        v-model="editedRole.customId"
                                    ></v-text-field>
                                </v-col>

                            </v-row>
                        </v-container>
                        <small>Los campos con * son obligatorios</small>
                    </v-card-text>
                    <v-card-actions>
                        <v-spacer></v-spacer>
                        <v-btn
                            color="primario"
                            text
                            @click="editRoleDialog = false"
                        >
                            Cancelar
                        </v-btn>
                        <v-btn
                            color="primario"
                            text
                            @click="editRole"
                        >
                            Guardar cambios
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>
            <!------------Seccion de dialogos ---------->
        </v-container>


        <!------------Seccion de Overlays ---------->

<!--        <v-overlay :value="isLoading"
                   absolute
                   opacity="1">
            <v-progress-circular
                indeterminate
                size="64"
            ></v-progress-circular>
        </v-overlay>-->
        <!------------Seccion de Overlays ---------->
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
                {text: 'ID del rol', value: 'customId'},
                {text: 'Nombre del rol', value: 'name'},
                {text: 'Fecha de creación', value: 'created_at'},
                {text: 'Acciones', value: 'actions', sortable: false},
            ],
            roles: [],
            //Roles models
            newRole: {
                name: '',
                customId: '',
            },
            editedRole: {
                id: '',
                name: '',
                customId: '',
            },
            deletedRoleId: 0,
            //Snackbars
            snackbar: {
                text: '...',
                status: false,
                timeout: 3000
            },
            //Dialogs
            createRoleDialog: false,
            deleteRoleDialog: false,
            editRoleDialog: false,

            //Overlays
            isLoading: true,
        }
    },
    async created() {
        await this.getAllRoles();
        this.isLoading = false;
    },
    methods: {
        openEditRoleModal: function (role) {
            this.editedRole = {...role};
            this.editRoleDialog = true;
        },
        editRole: async function () {
            //Verify request
            if (this.editedRole.name === '' || this.editedRole.id === '') {
                this.snackbar.text = 'Debes proporcionar un nombre y Id para el nuevo rol';
                this.snackbar.status = true;
                return;
            }
            //Recollect information
            let data = {
                id: this.editedRole.id,
                name: this.editedRole.name,
                customId: this.editedRole.customId
            }

            try {
                let request = await axios.patch(route('api.roles.update', {'role': this.editedRole.id}), data);
                this.editRoleDialog = false;
                this.snackbar.text = request.data.message;
                this.snackbar.status = true;
                this.getAllRoles();

                //Clear role information
                this.editedRole = {
                    id: '',
                    name: '',
                    customId: '',
                };
            } catch (e) {
                this.snackbar.text = prepareErrorText(e);
                this.snackbar.status = true;
            }
        },

        confirmDeleteRole: function (role) {
            this.deletedRoleId = role.id;
            this.deleteRoleDialog = true;
        },

        deleteRole: async function (roleId) {
            try {
                let request = await axios.delete(route('api.roles.destroy', {role: roleId}));
                this.deleteRoleDialog = false;
                this.snackbar.text = request.data.message;
                this.snackbar.status = true;
                this.getAllRoles();

            } catch (e) {
                this.snackbar.text = e.response.data.message;
                this.snackbar.status = true;
            }

        },
        getAllRoles: async function () {
            let request = await axios.get(route('api.roles.index'));
            this.roles = request.data;
        },
        createRole: async function () {
            if (this.newRole.name === '' || this.newRole.id === '') {
                this.snackbar.text = 'Debes proporcionar un nombre y Id para el nuevo rol';
                this.snackbar.status = true;
                return;
            }

            let data = {
                name: this.newRole.name,
                customId: this.newRole.id
            }
            //Clear role information
            this.newRole = {
                name: '',
                id: ''
            }
            try {
                let request = await axios.post(route('api.roles.index'), data);
                this.createRoleDialog = false;
                this.snackbar.text = request.data.message;
                this.snackbar.status = true;
                this.getAllRoles();
            } catch (e) {
                this.snackbar.text = e.response.data.message;
                this.snackbar.status = true;
            }

        }
    },


}
</script>
