<template>
    <v-app >
        <v-main>
            <v-container fluid fill-height>
                <v-layout align-center justify-center>
                    <v-flex xs12 sm8 md4>
                        <v-card class="elevation-12">
                            <v-toolbar dark color="primary">
                                <v-toolbar-title>Cooperativa Uni2</v-toolbar-title>
                            </v-toolbar>
                            <v-card-text>
                                <form @submit.prevent="submit">
                                    <v-text-field
                                        v-model="form.email"
                                        name="username"
                                        label="Usuario"
                                        type="text"
                                        placeholder="Correo ingresado en formulario de registro"
                                        required
                                    ></v-text-field>
                                    <div v-if="form.errors.email" class="text-red-500 text-xs mt-1" style="color: red">
                                        {{form.errors.email}}
                                    </div>

                                    <v-text-field
                                        v-model="form.password"
                                        name="password"
                                        label="Contraseña"
                                        type="password"
                                        placeholder="Contraseña"
                                        required
                                    ></v-text-field>
                                    <div v-if="form.errors.password" class="text-red-500 text-xs mt-1" style="color: red">
                                        {{form.errors.password}}
                                    </div>
                                    <div v-if="form.errors.approve" class="text-red-500 text-xs mt-1" style="color: red">
                                        {{form.errors.approve}}
                                    </div>
                                    <v-btn type="submit" class="mt-4" color="primary" value="log in">Ingresar</v-btn>
                                </form>
                            </v-card-text>
                        </v-card>

                    </v-flex>
                </v-layout>
            </v-container>
        </v-main>
    </v-app>
</template>

<script>
export default {
    name: "Login",
    data() {
        return {
            form: this.$inertia.form({
                email: '',
                password: '',
                remember: true
            })
            // username: "",
            // password: "",
        };
    },
    methods: {

        submit() {
            this.form.post(route('login.validation'));
        }


        // login() {
        //     const { username } = this;
        //     console.log(username + "logged in")
        // },
    },
};
</script>
