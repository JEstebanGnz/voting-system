<template>
    <AuthenticatedLayout>
        <Snackbar :timeout="snackbar.timeout" :text="snackbar.text" :type="snackbar.type"
                  :show="snackbar.status" @closeSnackbar="snackbar.status = false"></Snackbar>

        <v-container>
            <div class="d-flex flex-column align-end mb-8">
                <h2 class="align-self-start" > Miembros de {{this.board.description}} </h2>
                <div>
                    <v-btn
                        color="primario"
                        class="grey--text text--lighten-4"
                        @click="createNewLine"
                        :disabled="canAddMoreLines === false"
                    >
                        Agregar Renglón
                    </v-btn>
                </div>
            </div>

            <v-card class="mt-4">
                <v-data-table
                    loading-text="Cargando, por favor espere..."
                    :headers="headers"
                    :items="members"
                    :items-per-page="20"
                    :footer-props="{
                        'items-per-page-options': [10,20,30,-1]
                    }"
                    class="elevation-1"
                >
                    <template v-slot:item.actions="{ item }">
                        <v-icon
                            class="mr-2 primario--text"
                            @click="editLine(item)"
                        >
                            mdi-pencil
                        </v-icon>
                        <v-icon
                            class="primario--text"
                            @click="deleteLine(item)"
                        >
                            mdi-delete
                        </v-icon>
                        <v-icon
                            class="primario--text"
                            @click="editOrder(item)"
                        >
                            mdi-format-list-numbered
                        </v-icon>
                    </template>

                </v-data-table>
            </v-card>

            <v-dialog
                v-model="createBoardMembersDialog"
                persistent
                max-width="650px"
            >
                <v-card>
                    <v-card-title>
                        <span>
                        </span>
                        <span
                            class="text-h5">Agregando renglón </span>
                    </v-card-title>
                    <v-card-text>
                        <v-container>
                            <v-row>
                                <v-col cols="2" class="mt-5">
                                    <h4> Renglón {{this.currentAddingLine}}</h4>
                                </v-col>

                                <v-col cols="5">
                                    <v-autocomplete
                                        color="primario"
                                        v-model="currentAddingHead"
                                        :items="suitableUsersToBeElected"
                                        label="Titular"
                                        :item-text="(p)=>p.name"
                                        :item-value="(p)=>p"
                                    >

                                    </v-autocomplete>
                                </v-col>

                                <v-col cols="5">
                                    <v-autocomplete
                                        color="primario"
                                        v-model="currentAddingSubstitute"
                                        :items="suitableUsersToBeElected"
                                        label="Suplente"
                                        :item-text="(p)=>p.name"
                                        :item-value="(p)=>p"
                                    >

                                    </v-autocomplete>
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
                            @click="createBoardMembersDialog = false"
                        >
                            Cancelar
                        </v-btn>
                        <v-btn
                            color="primario"
                            text
                            @click="saveLine"
                        >
                            Guardar cambios
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <v-dialog
                v-model="editBoardMembersDialog"
                persistent
                max-width="650px"
            >
                <v-card>
                    <v-card-title>
                        <span>
                        </span>
                        <span
                            class="text-h5">Editando renglón </span>
                    </v-card-title>
                    <v-card-text>
                        <v-container>
                            <v-row>
                                <v-col cols="2" class="mt-5">
                                    <h4> Renglón</h4>
                                </v-col>

                                <v-col cols="5">
                                    <v-autocomplete
                                        color="primario"
                                        v-model="currentEditingHead"
                                        :items="editSuitableUsersToBeElected"
                                        label="Titular"
                                        :item-text="(p)=>p.name"
                                        :item-value="(p)=>p"
                                    >

                                    </v-autocomplete>

                                </v-col>

                                <v-col cols="5">
                                    <v-autocomplete
                                        color="primario"
                                        v-model="currentEditingSubstitute"
                                        :items="editSuitableUsersToBeElected"
                                        label="Suplente"
                                        :item-text="(p)=>p.name"
                                        :item-value="(p)=>p"
                                    >

                                    </v-autocomplete>

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
                            @click="editBoardMembersDialog = false"
                        >
                            Cancelar
                        </v-btn>
                        <v-btn
                            color="primario"
                            text
                            @click="saveUpdateLine"
                        >
                            Actualizar Renglón
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <v-dialog
                v-model="editBoardOrderDialog"
                persistent
                max-width="450px"
            >
                <v-card>
                    <v-card-title>
                        <span>
                        </span>
                        <span
                            class="text-h5"> Intercambio de posición del renglón {{this.currentPositionPriority}}</span>
                    </v-card-title>
                    <v-card-text>
                        <v-container>
                            <v-row>
                                <v-col cols="10">
                                    <v-text-field
                                        color="primario"
                                        required
                                        v-model="newPositionPriority"
                                        label="Define la nueva posición del renglón"
                                        type="number"
                                        min="1"
                                        :max="members.length"
                                        class="mt-2"
                                    ></v-text-field>
                                </v-col>
                            </v-row>
                        </v-container>
                    </v-card-text>
                    <v-card-actions>
                        <v-spacer></v-spacer>
                        <v-btn
                            color="primario"
                            text
                            @click="editBoardOrderDialog = false"
                        >
                            Cancelar
                        </v-btn>
                        <v-btn
                            color="primario"
                            text
                            @click="updateBoardOrder"
                        >
                            Actualizar Renglón
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

        </v-container>
    </AuthenticatedLayout>
</template>


<script>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import {InertiaLink} from "@inertiajs/inertia-vue";
import ConfirmDialog from "@/Components/ConfirmDialog";
import Snackbar from "@/Components/Snackbar";
import {prepareErrorText, showSnackbar} from "@/HelperFunctions"

export default {
    components: {
        ConfirmDialog,
        AuthenticatedLayout,
        InertiaLink,
        Snackbar,
    },

    props: {
        election: Object,
        board: Object,
    },

    data: () => {
        return {
            currentBoard : '',
            createBoardMembersDialog: false,
            editBoardMembersDialog: false,
            editBoardOrderDialog:false,

            //Snackbars
            snackbar: {
                text: "",
                type: 'alert',
                status: false,
                timeout: 2000,
            },

            headers:[
                {text: 'Posición', value: 'priority', align: 'center'},
                {text: 'Titular', value: 'head_name'},
                {text: 'Suplente', value: 'substitute_name'},
                {text: 'Acciones', value: 'actions', sortable:false}
            ],

            members: [],
            suitableUsersToBeElected : [],
            editSuitableUsersToBeElected : [],
            positions: [],
            positionsToSave: [],
            canAddMoreLines: true,
            sortedMembers: [],
            currentExistingLines: '',
            currentAddingLine: '',
            currentAddingHead: [],
            currentAddingSubstitute: [],
            currentEditingLine: '',
            currentEditingHead:  '',
            currentEditingSubstitute:  '',
            newPositionPriority:'',
            currentPositionPriority:'',
            currentLineId: '',
        }
    },

    async created() {
        await this.getBoardMembers();
        await this.getSuitableUsersToAdd();
        this.checkCanAddMoreLines();
    },

    methods: {

        async getBoardMembers(){
            let request = await axios.get(route('board.members.get', {election:this.election, board:this.board}));
            this.members = request.data;
        },

        async getSuitableUsersToAdd(){
            let request = await axios.get(route('users.suitableToAdd'));
            this.suitableUsersToBeElected = request.data;
            this.editSuitableUsersToBeElected = request.data;
            console.log(request.data);
        },

        checkCanAddMoreLines(){

            if(this.members.length > 0){
                let sortedMembers = JSON.parse(JSON.stringify(this.members));
                sortedMembers = sortedMembers.sort(
                    (p1, p2) => (p1.priority < p2.priority) ? 1 : (p1.priority > p2.priority) ? -1 : 0);

                console.log(sortedMembers, 'local');
                console.log(this.members, 'table');
                this.currentExistingLines = sortedMembers[0].priority;
                return sortedMembers[0].priority >= this.election.max_lines ? this.canAddMoreLines = false : this.canAddMoreLines = true

            }
        },

        createNewLine() {

            this.createBoardMembersDialog = true;
            this.currentAddingLine = this.currentExistingLines + 1;
        },

        async saveLine(){

            try {
                let request = await axios.post(route('board.members.save', {board: this.board}), {
                    data: {
                        head_id: this.currentAddingHead.id,
                        substitute_id: this.currentAddingSubstitute.id,
                        priority: this.currentAddingLine
                    }
                })
                this.createBoardMembersDialog= false;
                showSnackbar(this.snackbar, request.data.message, 'success');
                await this.getBoardMembers();
                await this.getSuitableUsersToAdd();
                this.checkCanAddMoreLines();

            } catch (e){
                showSnackbar(this.snackbar, prepareErrorText(e), 'alert');
            }
        },

        async deleteLine(line){

            try {
            let request = await axios.post(route('board.line.delete', {board: this.board}), {data:{
                head_id: line.head_id,
                substitute_id: line.substitute_id
                }})

            showSnackbar(this.snackbar, request.data.message, 'success');
            await this.getBoardMembers();
            await this.getSuitableUsersToAdd();
            this.checkCanAddMoreLines();

            } catch (e){
                showSnackbar(this.snackbar, prepareErrorText(e), 'alert');
            }

        },

        async editLine(item){

            console.log(item, 'item to edit');
            this.currentEditingLine = item.priority;
            this.currentEditingHead = {id: item.head_id,
                                        name: item.head_name}

            this.currentEditingSubstitute =
                {id: item.substitute_id,
                name: item.substitute_name}

            this.editSuitableUsersToBeElected.push(this.currentEditingHead);
            this.editSuitableUsersToBeElected.push(this.currentEditingSubstitute);
            this.editBoardMembersDialog = true;
            console.log(this.currentEditingHead, this.currentEditingSubstitute);

        },

        async saveUpdateLine(){

            console.log(this.currentEditingHead, this.currentEditingSubstitute);
            try {
                let request = await axios.post(route('board.members.save', {board: this.board}), {
                    data: {
                        head_id: this.currentEditingHead.id,
                        substitute_id: this.currentEditingSubstitute.id,
                        priority: this.currentEditingLine,
                        editing: true,
                    }
                })
                this.editBoardMembersDialog= false;
                showSnackbar(this.snackbar, request.data.message, 'success');
                await this.getBoardMembers();
                await this.getSuitableUsersToAdd();
                this.checkCanAddMoreLines();

            } catch (e){
                showSnackbar(this.snackbar, prepareErrorText(e), 'alert');
            }
        },

        editOrder(item){

            this.currentPositionPriority = item.priority;
            this.editBoardOrderDialog = true;
            this.currentEditingHead = item.head_id
            this.currentEditingSubstitute = item.substitute_id;
            this.currentLineId = item.id

        },

        async updateBoardOrder(){

          console.log(this.currentPositionPriority, this.newPositionPriority);

            try {
                let request = await axios.post(route('board.priorities.update', {board: this.board}), {
                    data: {
                        old_position: this.currentPositionPriority,
                        new_position: this.newPositionPriority,
                        line_id: this.currentLineId,
                        head_id: this.currentEditingHead,
                        substitute_id: this.currentEditingSubstitute
                    }
                })
                this.editBoardOrderDialog= false;
                showSnackbar(this.snackbar, request.data.message, 'success');
                await this.getBoardMembers();
                await this.getSuitableUsersToAdd();
                this.checkCanAddMoreLines();

            } catch (e){
                showSnackbar(this.snackbar, prepareErrorText(e), 'alert');
            }

        },

    }
    }
</script>
