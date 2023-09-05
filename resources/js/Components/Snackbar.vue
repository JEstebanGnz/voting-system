<template>
    <v-snackbar
        v-model="showDialog"
        :timeout="timeout"
        :color="getColor"
        top
        right
    >
        {{ text }}
        <template v-slot:action="{ attrs }">
            <v-btn
                text
                v-bind="attrs"
                @click="closeDialog"
            >
                Cerrar
            </v-btn>
        </template>
    </v-snackbar>
</template>

<script>
export default {
    props: {
        show: Boolean,
        timeout: Number,
        text: String,
        type: String
    },
    data: () => {
        return {
            showDialog: false,
        }
    },
    methods: {
        closeDialog() {
            this.$emit('closeSnackbar');
            this.showDialog = false;
        }
    },

    computed: {
        getColor() {
            if (this.type === 'success') {
                return 'green darken-1'
            }
            if (this.type === 'alert') {
                return 'red accent-2';
            }
            return 'red accent-2';
        }
    },

    watch: {
        show: function (newShow, oldShow) {
            this.showDialog = newShow;
        },
        showDialog: function (newShow, oldShow) {
            if (newShow === false && oldShow === true) {
                this.$emit('closeSnackbar');
            }
        }
    }
}

</script>
