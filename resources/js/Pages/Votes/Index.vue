<template>

    <AuthenticatedLayout>

        <div v-if="(election && ableToVote && !externalUserFinishedVoting) || (election && judicialAuthority && !externalUserFinishedVoting) " class="align-center px-6 " style="width: 100%">
            <h1 class="text-center">Emitiendo voto para la elección: {{election.name}}</h1>
            <h2 class="text-center">Nombre del votante actual: {{currentUser.name}}</h2>

            <v-divider class="my-6"></v-divider>
            <div class="fill-height my-4" v-if="isLoading">
                <v-row>
                    <v-col cols="4" v-for="(skeleton,key) in 4" :key="key">
                        <v-skeleton-loader type="card"></v-skeleton-loader>
                    </v-col>
                </v-row>
            </div>
            <template v-for="(votingOption,key) in votingOptions" v-if="!isLoading">
                <h2 class="mb-6 text-center"> Plancha {{key+1}}</h2>
                <v-row>
                    <v-col cols="12" class="mx-auto">
                        <vue-glow color="#1e3a62" mode="hex" elevation="20">
                            <v-card outlined>
                                <v-card-title>
                                <div v-for="(votingOptionLine, key) in votingOption.lines" id="content">
                                    <p class="grey--text"> <strong class="black--text"> Titular: </strong> {{votingOptionLine.head_name}} <br>
                                        <strong class="black--text"> Suplente:  </strong> {{votingOptionLine.substitute_name}}</p>
                                </div>
                                </v-card-title>
                                <v-card-actions class="d-flex justify-center mb-2">
                                    <v-btn
                                        @click="selectVotingOption(votingOption)"
                                        rounded
                                        color="primario"
                                        class="grey--text text--lighten-4"
                                        :disabled="votingOption.id === selectedVotingOption.id"
                                    >
                                            <span class="px-2">
                                                {{
                                                    votingOption.id === selectedVotingOption.id ? 'Seleccionado' : 'Seleccionar'
                                                }}
                                            </span>
                                    </v-btn>
                                </v-card-actions>
                            </v-card>
                        </vue-glow>
                    </v-col>
                </v-row>
                <v-divider class="my-8" v-if="key !== (votingOptions.length-1)"></v-divider>
            </template>

            <div class="d-flex justify-center mt-12" v-if="(!isLoading && ableToVote) || (!isLoading && judicialAuthority)">
                <v-btn
                    @click="vote()"
                    color="primario"
                    large
                    class="grey--text text--lighten-4">
                    Emitir voto
                </v-btn>
            </div>
        </div>


        <div v-else-if="!this.election" style="margin: 10px auto 10px auto; text-align: center">
            <h2> En este momento no hay ninguna votación activa, por favor espera a las indicaciones del presidente</h2>

            <div v-if="!isExternalUser">
                <h2 style="margin-top: 20px"> Usuario: </h2>
                <h2>  {{this.currentUser.name}} </h2>
            </div>

            <div v-if="onRepresentationUsersBeforeVoting.length > 0">
            <h3 style="margin-top: 20px"> Poderes: </h3>

            <h3 v-for="user in onRepresentationUsersBeforeVoting" style="margin-top: 2px" >
                -{{user.name}}
            </h3>
            </div>
        </div>

        <div v-if="!ableToVote && !judicialAuthority" style="margin: 10px auto 10px auto">
            <h2> Gracias por votar, por favor espera a las indicaciones del presidente</h2>
        </div>

        <div v-if="this.externalUserFinishedVoting && this.election" style="margin: 10px auto 10px auto">
            <h2> Gracias por votar, por favor espera a las indicaciones del presidente</h2>
        </div>


        <!-- SNACKBAR-->
        <v-snackbar
            v-model="snackbar.status"
            :timeout="snackbar.timeout"
            color="red accent-2"
            top
            right
        >
            {{ snackbar.text }}
        </v-snackbar>
        <!-- dialogs-->

        <v-dialog v-model="showDialog" width="500" persistent>
            <v-card>
                <v-card-title>
                    ¡Gracias por votar!
                </v-card-title>
                <v-card-text>
                    Tu voto ha sido registrado exitosamente
                </v-card-text>
                <v-card-actions class="d-flex justify-end">
                    <v-btn
                        @click="checkCurrentStatus()"
                        color="primario"
                        class="grey--text text--lighten-4">
                        Finalizar
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

    </AuthenticatedLayout>
</template>

<style>

#content{
    overflow-wrap: break-word;
    word-wrap: break-word;
    word-break: break-word;
    margin: auto;
    text-align: center;
}

#content2{
    overflow-wrap: break-word;
    word-wrap: break-word;
    word-break: break-word;
    margin: auto;
    text-align: center;
}

</style>

<script>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import ConfirmDialog from "@/Components/ConfirmDialog";
import {InertiaLink} from "@inertiajs/inertia-vue";
import Snackbar from "@/Components/Snackbar";
import Board from "@/Models/Board";
import {prepareErrorText, showSnackbar} from "@/HelperFunctions";
import Election from "@/Models/Election";
import VueGlow from 'vue-glow';

export default {
    components: {
        ConfirmDialog,
        AuthenticatedLayout,
        InertiaLink,
        Snackbar,
        VueGlow
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
            election: [],
            boards: [],
            votingOptions: [],
            headers: [
                {text: 'Descripción', value: 'description'},
                {text: 'Elección', value: 'election_name'},
                {text: 'Acciones', value: 'actions'},
            ],
            selectedVotingOption: '',
            showDialog: false,
            showAuthorityVoteDialog: false,
            isLoading: true,
            alreadyVoted: false,
            judicialAuthority: false,
            onRepresentationUsers: [],
            onRepresentationUsersBeforeVoting: [],
            selectedOnRepresentationUser:'',
            realUser: '',
            currentUser: '',
            ableToVote: true,
            isExternalUser: false,
            externalUserFinishedVoting: false,
        }
    },

    async created(){
        this.userInfo();
        await this.getActiveElection();
        await this.checkCurrentUserInfo();
        this.isLoading = false;

    },

    methods: {

        userInfo(){
            console.log(this.$page.props.user, 'INFORMACIÓN DEL USUSARIO');
        },

       async checkCurrentUserInfo(){
            this.currentUser = this.$page.props.user;

            if(this.currentUser.external_user == true){
                this.isExternalUser = true;
            }

            if(!this.ableToVote && this.judicialAuthority || this.isExternalUser){

                for (const representadedUser of this.onRepresentationUsers) {

                    console.log(representadedUser, 'representadedUser');

                    let request = await axios.get(route('votes.userRepresentaded.isAbleToVote',{
                        user:representadedUser,
                        election:this.election}));

                    if (request.data == true){
                        this.currentUser = representadedUser;
                        return;
                    }

                    else{
                        this.judicialAuthority = false;
                    }

                }
            }

            if(!this.judicialAuthority && this.isExternalUser){
                console.log("malparidos malandros");
                this.externalUserFinishedVoting = true;
            }

        },

        getActiveElection: async function(){
            let request = await axios.get(route('elections.active'))
            console.log(request.data);
            this.election = request.data;

            if(this.election !== ""){
                await this.judicialAuthorityUsers();
                await this.isAbleToVote();
                this.votingOptions = this.election.boards;
            }

            else{
                await this.judicialAuthorityBeforeVoting();
            }
        },

        async judicialAuthorityBeforeVoting(){

            let request = await axios.get(route('judicialA.users.bVoting',
                {user:this.$page.props.user}))
            console.log(request.data);
            this.onRepresentationUsersBeforeVoting = request.data;

        },

        selectVotingOption: function (votingOption) {
            this.selectedVotingOption = votingOption
        },

        async judicialAuthorityUsers(){

            let request = await axios.get(route('judicialA.users',
                {user:this.$page.props.user,
                election:this.election}))

            console.log(request.data, 'usuarios sobre los que va a poder votar');
            this.onRepresentationUsers = request.data;
            this.judicialAuthority = this.onRepresentationUsers.length > 0;

        },

        async vote(){

            if (this.selectedVotingOption === ''){
                this.snackbar.text = 'Debes seleccionar una plancha antes de votar';
                this.snackbar.status = true;
            }

            else {
                let data = {user_id: this.currentUser.id,
                    board_id: this.selectedVotingOption.id,
                    election_id:  this.election.id}

                console.log(data, 'DATAAAAAAA')

                try {
                    let request = await axios.post(route('api.votes.store'), {userVote: data});
                    await this.getActiveElection();
                    this.showDialog = true;
                } catch (e) {
                    this.snackbar.text = e.response.data.message;
                    this.snackbar.status = true;
                }

            }
        },

        async isAbleToVote(){

                let request = await axios.post(route('votes.user.isAbleToVote'),{
                    user:this.$page.props.user,
                    election:this.election});

                this.ableToVote = request.data;

                if (this.judicialAuthority === true){
                    return true
                }
        },

        async checkCurrentStatus(){
            await this.checkCurrentUserInfo();
            this.showDialog = false;
            window. scrollTo({ top: 0, left: 0});

        }
    }
}
</script>
