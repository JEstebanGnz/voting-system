<template>
    <v-dialog
        v-model="showDialog"
        persistent
        max-width="350px"
    >
        <v-card>
            <v-card-title class="text-h5 text-center">
                <slot name="title"></slot>
            </v-card-title>

            <v-card-text class="text-h6 text-justify">
                <slot></slot>
            </v-card-text>
            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn
                    color="primario"
                    text
                    @click="$emit('canceled-dialog')"
                >
                    Cancelar
                </v-btn>
                <v-btn
                    color="primario"
                    text
                    @click="$emit('confirmed-dialog')"
                >
                    <slot name="confirm-button-text"></slot>
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script>
export default {
    props: {
        show: Boolean
    },
    data: () => {
        return {
            showDialog: false,
        }
    },

    watch: {
        show: function (newShow, oldShow) {
            this.showDialog = newShow;
        }
    }
}

</script>
