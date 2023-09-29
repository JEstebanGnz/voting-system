<template>
    <AuthenticatedLayout>
    <Snackbar :timeout="snackbar.timeout" :text="snackbar.text" :type="snackbar.type"
              :show="snackbar.status" @closeSnackbar="snackbar.status = false"></Snackbar>

    <v-container>
        <div class="d-flex flex-column align-end mb-8">
            <h2 class="align-self-start">Gestionar Elecciones </h2>
            <div>
                <v-btn
                    color="#1e3a62"
                    class="grey--text text--lighten-4"
                    @click="setElectionDialogToCreateOrEdit('create')"
                >
                    Crear nueva elección
                </v-btn>
            </div>
        </div>


        <!--Inicia tabla-->
        <v-card>
            <v-card-title>
                <v-text-field
                    v-model="search"
                    append-icon="mdi-magnify"
                    label="Filtrar elecciones por nombre"
                    single-line
                    hide-details
                ></v-text-field>
            </v-card-title>

        <v-data-table
                :search="search"
                loading-text="Cargando, por favor espere..."
                :loading="isLoading"
                :headers="headers"
                :items="elections"
                :items-per-page="20"
                :footer-props="{
                        'items-per-page-options': [20,50,100,-1]
                    }"
                class="elevation-1"
                :item-class="getRowColor">

            <template v-slot:item.actions="{ item }">
                <v-icon
                    class="mr-2 primario--text"
                    @click="setElectionDialogToCreateOrEdit('edit', item)"
                >
                    mdi-pencil
                </v-icon>
                <v-icon
                    class="mr-2 primario--text"
                    @click="confirmDeleteElection(item)"
                >
                    mdi-delete
                </v-icon>

                <v-tooltip top>
                    <template v-slot:activator="{on,attrs}">
                            <v-icon
                                @click="openElectionResults(item)"
                                v-bind="attrs"
                                v-on="on"
                                class="mr-2 primario--text"
                            >
                                mdi-clipboard-check
                            </v-icon>
                    </template>
                    <span>Ver resultados de la elección</span>
                </v-tooltip>

                <v-tooltip top>
                    <template v-slot:activator="{on,attrs}">
                        <InertiaLink :href="route('boards.index.view', {electionId:item.id})">
                            <v-icon
                                v-bind="attrs"
                                v-on="on"
                                class="mr-2 primario--text"
                            >
                                mdi-format-list-bulleted
                            </v-icon>
                        </InertiaLink>
                    </template>
                    <span>Gestionar Elección</span>
                </v-tooltip>

                <v-icon v-if="!(item.is_active)"
                        class="mr-2 primario--text"
                        @click="setElectionAsActive(item.id)"
                >
                    mdi-cursor-default-click
                </v-icon>

                <v-icon v-if="(item.is_active)"
                        class="mr-2 primario--text"
                        @click="deactivateElection(item.id)"
                >
                    mdi-close-circle
                </v-icon>
            </template>

            </v-data-table>

        </v-card>
        <!--Acaba tabla-->

        <!--Crear o editar elección -->
        <v-dialog
            v-model="createOrEditDialog.dialogStatus"
            persistent
            max-width="650px"
        >
            <v-card>
                <v-card-title>
                        <span>
                        </span>
                    <span class="text-h5">Crear una nueva elección</span>
                </v-card-title>
                <v-card-text>
                    <v-container>
                        <v-row>
                            <v-col cols="12">
                                <v-text-field
                                    label="Nombre de la elección"
                                    required
                                    v-model="$data[createOrEditDialog.model].name"
                                ></v-text-field>
                            </v-col>

                            <v-col cols="12">
                                <v-text-field
                                    label="Descripción de la elección"
                                    required
                                    v-model="$data[createOrEditDialog.model].description"
                                ></v-text-field>
                            </v-col>

                            <v-col cols="10">
                                <v-text-field
                                    color="primario"
                                    required
                                    v-model="$data[createOrEditDialog.model].max_lines"
                                    label="Define el número de curules a asignar en la elección"
                                    type="number"
                                    min="1"
                                    max="10"
                                    class="mt-2"
                                ></v-text-field>
                                <span> Una vez crees la primera plancha de esta elección <strong> YA NO PODRÁS MODIFICAR EL NÚMERO DE CURULES </strong></span>
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
                        @click="createOrEditDialog.dialogStatus = false"
                    >
                        Cancelar
                    </v-btn>
                    <v-btn
                        color="primario"
                        text
                        @click="handleSelectedMethod"
                    >
                        Guardar cambios
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!--Confirmar borrar Elección-->
        <confirm-dialog
            :show="deleteElectionDialog"
            @canceled-dialog="deleteElectionDialog = false"
            @confirmed-dialog="deleteElection(deletedElectionId)"
        >
            <template v-slot:title>
                Estas a punto de eliminar la elección
            </template>

            ¡Cuidado! esta acción es irreversible

            <template v-slot:confirm-button-text>
                Borrar
            </template>
        </confirm-dialog>
    </v-container>
    </AuthenticatedLayout>
</template>


<script>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import {InertiaLink} from "@inertiajs/inertia-vue";
import {prepareErrorText, showSnackbar} from "@/HelperFunctions"
import ConfirmDialog from "@/Components/ConfirmDialog";
import Snackbar from "@/Components/Snackbar";
import Election from "@/Models/Election";

export default {
    components: {
        ConfirmDialog,
        AuthenticatedLayout,
        InertiaLink,
        Snackbar,
    },

    data: () => {
        return {
            //Table info
            snackbar: {
                text: "",
                type: 'alert',
                status: false,
                timeout: 2000,
            },

            search: '',
            elections: [],
            headers: [
                {text: 'Nombre', value: 'name'},
                {text: 'Descripción', value: 'description'},
                {text: 'Curules', value: 'max_lines'},
                {text: 'Acciones', value: 'actions'},
            ],

            newElection: new Election(),
            editedElection: new Election(),

            createOrEditDialog: {
                model: 'newElection',
                method: 'createElection',
                dialogStatus: false,
            },

            deletedElectionId : 0,
            deleteElectionDialog: false,
            isLoading: true,
        }
    },

    async created(){
        await this.getElections();
        this.isLoading = false;
    },

    methods: {

        testSnackbar(){
            showSnackbar(this.snackbar, 'Hola', 'success');
        },

        openElectionResults(election){
            window.open(route('elections.report', {election:election}));
        },

        setElectionAsActive: async function (electionId){
            try{
                let request = await axios.post(route('api.elections.setActive', {'election': electionId}))
                this.createOrEditDialog.dialogStatus = false;
                showSnackbar(this.snackbar, request.data.message, 'success');
                await this.getElections();
            } catch (e){
                showSnackbar(this.snackbar, prepareErrorText(e), 'alert');
            }
        },

        deactivateElection: async function (electionId){

            try{
                let request = await axios.post(route('api.elections.deactivate', {'election': electionId}))
                this.createOrEditDialog.dialogStatus = false;
                showSnackbar(this.snackbar, request.data.message, 'success');
                await this.getElections();
            } catch (e){
                showSnackbar(this.snackbar, prepareErrorText(e), 'alert');
            }

        },

        async getElections(){
            let request = await axios.get(route('api.elections.index'));
            this.elections = request.data;
        },

        setElectionDialogToCreateOrEdit(which, item = null){

            if (which === 'create') {
                this.createOrEditDialog.method = 'createElection';
                this.createOrEditDialog.model = 'newElection';
                this.createOrEditDialog.dialogStatus = true;
            }

            if (which === 'edit') {
                this.editedElection = Election.fromModel(item);
                this.createOrEditDialog.method = 'editElection';
                this.createOrEditDialog.model = 'editedElection';
                this.createOrEditDialog.dialogStatus = true;
            }
        },

        createElection: async function (){

            if (this.newElection.hasEmptyProperties()){
                showSnackbar(this.snackbar, 'Debes diligenciar todos los campos obligatorios', 'red', 2000);
                return;
            }

            let data = this.newElection.toObjectRequest();
            this.newElection = new Election();

            console.log(this.newElection);


            try{
                let request = await axios.post(route('api.elections.store'), data);
                this.createOrEditDialog.dialogStatus = false;
                showSnackbar(this.snackbar, request.data.message, 'success', 2000);
                await this.getElections();
            }

            catch (e){
                showSnackbar(this.snackbar, e.response.data.message, 'alert', 3000);
            }
        },

        editElection: async function (){

            if (this.editedElection.hasEmptyProperties()){
                showSnackbar(this.snackbar, 'Debes diligenciar todos los campos obligatorios', 'red', 2000);
                return;
            }
            //Recollect information
            let data = this.editedElection.toObjectRequest();

            try {
                let request = await axios.patch(route('api.elections.update', {'election': this.editedElection.id}), data);
                this.createOrEditDialog.dialogStatus = false;
                showSnackbar(this.snackbar, request.data.message, 'success');
                await this.getElections();

                //Clear role information
                this.editedElection = new Election();
            } catch (e) {
                showSnackbar(this.snackbar, prepareErrorText(e), 'alert');
            }
        },

        confirmDeleteElection: async function (election){

            this.deletedElectionId = election.id
            this.deleteElectionDialog = true;

        },

        deleteElection: async function (electionId) {
            try {
                let request = await axios.delete(route('api.elections.destroy', {election: electionId}));
                this.deleteElectionDialog = false;
                showSnackbar(this.snackbar, request.data.message, 'success');
                await this.getElections();
            } catch (e) {
                showSnackbar(this.snackbar, e.response.data.message, 'red', 3000);
            }
        },

        getRowColor: function (item) {
            return item.is_active ? 'green lighten-5' : '';
        },

        handleSelectedMethod: function () {
            this[this.createOrEditDialog.method]();
        },

    }
}
</script>

