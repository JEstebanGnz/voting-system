<template>
    <AuthenticatedLayout>
        <Snackbar :timeout="snackbar.timeout" :text="snackbar.text" :type="snackbar.type"
                  :show="snackbar.status" @closeSnackbar="snackbar.status = false"></Snackbar>

        <v-container>
            <div class="d-flex flex-column align-end mb-8">
                <h2 class="align-self-start">Gestionar Planchas</h2>
                <div>
                    <v-btn
                        color="#1e3a62"
                        class="grey--text text--lighten-4"
                        @click="setElectionDialogToCreateOrEdit('create')"
                    >
                        Crear nueva plancha
                    </v-btn>
                </div>
            </div>


            <!--Inicia tabla-->
            <v-card style="max-width: 90%; margin: auto">
                <v-card-title>
                    <v-text-field
                        v-model="search"
                        append-icon="mdi-magnify"
                        label="Filtrar planchas por nombre"
                        single-line
                        hide-details
                    ></v-text-field>
                </v-card-title>

                <v-data-table
                    :search="search"
                    loading-text="Cargando, por favor espere..."
                    :loading="isLoading"
                    :headers="headers"
                    :items="boards"
                    :items-per-page="20"
                    :footer-props="{
                        'items-per-page-options': [20,50,100,-1]
                    }"
                    class="elevation-4"
                >

                    <template v-slot:item.description="{item}" >

                        <p class="card-text" style="font-size: 1.2em" v-html="item.description" />

                    </template>


                    <template v-slot:item.actions="{ item }">
                        <v-icon
                            class="mr-2 primario--text"
                            @click="setElectionDialogToCreateOrEdit('edit', item)"
                        >
                            mdi-pencil
                        </v-icon>
                        <v-icon
                            class="mr-2 primario--text"
                            @click="confirmDeleteBoard(item)"
                        >
                            mdi-delete
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
                        <span class="text-h5">Crear una nueva plancha</span>
                    </v-card-title>
                    <v-card-text>
                        <v-container>
                            <v-row>
                                <v-col cols="12">
                                        <VueTrix  v-model="$data[createOrEditDialog.model].description"
                                                  placeholder="Descripción (integrantes) de la plancha"/>
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
                :show="deleteBoardDialog"
                @canceled-dialog="deleteBoardDialog = false"
                @confirmed-dialog="deleteBoard(deletedBoardId)"
            >
                <template v-slot:title>
                    Estas a punto de eliminar la plancha seleccionada
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
import Board from "@/Models/Board";
import VueTrix from "vue-trix";



export default {
    components: {
        ConfirmDialog,
        AuthenticatedLayout,
        InertiaLink,
        Snackbar,
        VueTrix

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
            boards: [],
            headers: [
                {text: 'Descripción', value: 'description'},
                {text: 'Elección', value: 'election_name'},
                {text: 'Acciones', value: 'actions'},
            ],
            newBoard: new Board(null,'',route().params.electionId),
            editedBoard: new Board(),
            createOrEditDialog: {
                model: 'newBoard',
                method: 'createBoard',
                dialogStatus: false,
            },
            deletedBoardId : 0,
            deleteBoardDialog: false,
            isLoading: true,
        }
    },

    async created(){
        await this.getBoards();
        this.isLoading = false;
    },

    methods: {

        async getBoards(){

            let request = await axios.get(route('api.elections.boards', {electionId: route().params.electionId}));
            console.log(request.data);
            this.boards = request.data
        },

        setElectionDialogToCreateOrEdit(which, item = null){

            if (which === 'create') {
                this.createOrEditDialog.method = 'createBoard';
                this.createOrEditDialog.model = 'newBoard';
                this.createOrEditDialog.dialogStatus = true;
            }

            if (which === 'edit') {
                this.editedBoard = Board.fromModel(item);
                this.editedBoard.election_id = route().params.electionId
                this.createOrEditDialog.method = 'editBoard';
                this.createOrEditDialog.model = 'editedBoard';
                this.createOrEditDialog.dialogStatus = true;
            }
        },

        createBoard: async function (){

            if (this.newBoard.hasEmptyProperties()){

                showSnackbar(this.snackbar, 'Debes diligenciar todos los campos obligatorios', 'red', 2000);
                return;
            }
            let data = this.newBoard.toObjectRequest();

            try{
                let request = await axios.post(route('api.boards.store'), data);
                this.createOrEditDialog.dialogStatus = false;
                showSnackbar(this.snackbar, request.data.message, 'success', 2000);
                await this.getBoards();
            }

            catch (e){
                showSnackbar(this.snackbar, e.response.data.message, 'alert', 3000);
            }
        },

        editBoard: async function (){

            if (this.editedBoard.hasEmptyProperties()){
                showSnackbar(this.snackbar, 'Debes diligenciar todos los campos obligatorios', 'red', 2000);
                return;
            }

            console.log(this.editedBoard);

            //Recollect information
            let data = this.editedBoard.toObjectRequest();

            try {
                let request = await axios.patch(route('api.boards.update', {'board': this.editedBoard.id}), data);
                this.createOrEditDialog.dialogStatus = false;
                showSnackbar(this.snackbar, request.data.message, 'success');
                await this.getBoards();

                //Clear role information
                this.editedBoard = new Election();
            } catch (e) {
                showSnackbar(this.snackbar, prepareErrorText(e), 'alert');
            }
        },

        confirmDeleteBoard: async function (board){

            this.deletedBoardId = board.id
            this.deleteBoardDialog = true;
        },

        deleteBoard: async function (boardId) {
            try {
                let request = await axios.delete(route('api.boards.destroy', {board: boardId}));
                this.deleteBoardDialog = false;
                showSnackbar(this.snackbar, request.data.message, 'success');
                await this.getBoards();
            } catch (e) {
                showSnackbar(this.snackbar, e.response.data.message, 'red', 3000);
            }
        },

        handleSelectedMethod: function () {
            this[this.createOrEditDialog.method]();
        },

    }
}
</script>

