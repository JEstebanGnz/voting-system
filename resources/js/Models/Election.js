import {checkIfModelHasEmptyProperties, toObjectRequest} from "@/HelperFunctions";

export default class Election {
    toObjectRequest() {
        return toObjectRequest(this);
    }

    hasEmptyProperties() {
        return checkIfModelHasEmptyProperties(this);
    }

    static fromModel(model) {
        return new Election(model.id, model.name, model.description, model.is_active);
    }

    constructor(id = null, name = '', description = '', is_active = false) {
        this.id = id;
        this.name = name;
        this.description = description;
        this.is_active = is_active;

        this.dataStructure = {
            id: null,
            name: 'required',
            description: 'required',
            is_active: 'required',

        }
    }
}
