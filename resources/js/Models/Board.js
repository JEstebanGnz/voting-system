import {checkIfModelHasEmptyProperties, toObjectRequest} from "@/HelperFunctions";

export default class Board {
    toObjectRequest() {
        return toObjectRequest(this);
    }

    hasEmptyProperties() {
        return checkIfModelHasEmptyProperties(this);
    }

    static fromModel(model) {
        return new Board(model.id, model.description, model.election_id);
    }

    constructor(id = null, description = '', election_id = null) {
        this.id = id;
        this.description = description;
        this.election_id = election_id;

        this.dataStructure = {
            id: null,
            description: 'required',
            election_id: 'required',

        }
    }
}
