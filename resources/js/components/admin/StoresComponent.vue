<template>
	<v-app id="inspire">
	    <v-data-table item-key="name" class="elevation-1" :loading="loading" loading-text="Loading... Please wait"
	    :headers="headers" :options.sync="options" :server-items-length="stores.total" :items="stores.data" show-select @input="selectAll" :footer-props="footerProps">
	        <template v-slot:top>
	            <v-toolbar flat>
	                <v-toolbar-title >Store Management System</v-toolbar-title>
	                <v-divider class="mx-4" inset vertical></v-divider>
	                <v-spacer></v-spacer>
	                <v-dialog v-model="dialog" max-width="500px">
	                    <template v-slot:activator="{ on }">
	                        <v-btn color="primary" dark class="mb-2 mr-2" @click="deleteAll" disabled>Delete</v-btn>
	                    </template>
	                </v-dialog>
	            </v-toolbar>
	        	<v-text-field @input="searchIt" append-icon="mdi-magnify" class="mx-4" label="Search..." single-line hide-details></v-text-field>
	        </template>  
	        <template v-slot:item.category="{ item }">
		        <v-edit-dialog large block persistent :return-value.sync="item.category"  @save="updateCategory(item)" >
		          	{{item.category}}
		          	<template v-slot:input>
		            	<h4>Change Category</h4>
		            	<v-select :rules="[rules.required]" :items="categories" v-model="item.category" color="error" label="Select Category"></v-select>
		          	</template>
		        </v-edit-dialog>
		    </template>
	        <template v-slot:item.status="{ item }">
		        <v-edit-dialog large block persistent :return-value.sync="item.status"  @save="updateStatus(item)" >
		          	{{item.category}}
		          	<template v-slot:input>
		            	<h4>Change Status</h4>
		            	<v-select :rules="[rules.required]" :items="status" v-model="item.status" color="error" label="Select Status"></v-select>
		          	</template>
		        </v-edit-dialog>
		    </template>
	        <template v-slot:item.actions="{ item }">
	            <v-icon small @click="deleteItem(item)">
	                mdi-delete
	            </v-icon>
	        </template>
	        <template v-slot:no-data>
	            <v-btn color="primary" @click="initialize">Reset</v-btn>
	        </template>
	    </v-data-table>
		<v-snackbar v-model="snackbar">
		    {{ text }}
			<v-btn color="error" text @click="snackbar = false">
		    	Close
			</v-btn>
		</v-snackbar>
	</v-app>
</template>
<script>
    export default {
        data: () => ({
        	valid: true,
        	dialog: false,
        	loading: false,
        	snackbar: false,
        	selected: [],
        	text: '',
        	categories: [],
        	success: '',
        	error: '',
        	options: {
        		itemsPerPage: 5,
        		sortBy: ['id'],
        		sortDesc: [false]
        	},
        	rules: {
        		required: v => !!v || "This Field Required",
        		min: v => v.length >= 5 || "Minimum 5 Characters Required",
        		validEmail: v => /.+@.+\..+/.test(v) || "E-mail must be valid",
        	},
        	footerProps: {
				itemsPerPageOptions: [5, 10, 15],
				itemsPerPageText: 'Stores Per Page',
				'show-current-page': true,
				'show-first-last-page': true
        	},
        	headers: [
		        { text: '#', align: 'left', sortable: false, value: 'id' },
		        { text: 'Name', value: 'name' },
		        { text: 'Category', value: 'category' },
		        { text: 'Status', value: 'status' },
		        { text: 'Logo', sortable: false, value: 'logo' },
		        { text: 'Actions', sortable: false, value: 'actions' },
		    ],
		    stores: [],
		    editedIndex: -1,
		    editedItem: {
		    	id: '',
		        name: '',
		        category: '',
		        status: '',
		    },
		    defaultItem: {
		    	id: '',
		        name: '',
		        category: '',
		        status: '',
		        created_at: '',
		        updated_at: '',
		    },
        }),
        computed: {
	      	formTitle () {
	        	return this.editedIndex === -1 ? 'New Store' : 'Edit Store'
	      	},
	    },
	    watch: {
		    dialog (val) {
		        val || this.close()
		    },
		    options: {
		    	handler(e) {
		    		const sortBy = e.sortBy.length > 0 ? e.sortBy[0].trim() : 'id';
		    		const orderBy = e.sortDesc[0] ? 'desc' : 'asc';
					axios.get(`/api/stores`, {params: {'page': e.page,'per_page': e.itemsPerPage, 'sort_by': sortBy, 'order_by': orderBy}})
					.then(res => {
						this.stores = res.data.stores;
         				this.categories = res.data.categories;
					})
					.catch(err => {
						if(err.response.status == 401) {
							localStorage.removeItem('token');
							this.$router.push('/login');
						}
					})
				},
				deep: true
		   	},
	    },
	    created () {
	      	this.initialize()
	    },
	    methods: {
	    	updateStatus(item) {
	    		const index = this.stores.data.indexOf(item);
		      	axios.post("/api/store/status", { status: item.status, store: item.id })
		        .then(res => {
		          	this.text = res.data.store.name + "'s Status Updated to " + res.data.store.status;
		          	this.snackbar = true;
		        })
		        .catch(error => {
		          	this.text = error.response.data.store.name + "'s Status Cannot Be Updated to " + error.response.data.store.status;
		          	this.stores.data[index].status = error.response.data.store.status;
		          	this.snackbar = true;
		        });
	    	},
	    	updateCategory(item) {
	    		const index = this.stores.data.indexOf(item);
		      	axios.post("/api/store/category", { category: item.category, store: item.id })
		        .then(res => {
		          	this.text = res.data.store.name + "'s Category Updated to " + res.data.store.category;
		          	this.snackbar = true;
		        })
		        .catch(error => {
		          	this.text = error.response.data.store.name + "'s Category Cannot Be Updated to " + error.response.data.store.category;
		          	this.stores.data[index].category = error.response.data.store.category;
		          	this.snackbar = true;
		        });
	    	},
	    	selectAll(e) {
	    		this.selected = []
	    		if(e.length > 0) {
	    			this.selected = e
	    			//this.selected = e.map(val => val.id)
	    		}
	    	},
	    	deleteAll() {
	    		let decide = confirm('Are you sure you want to delete these items?')
		        if(decide) {
		        	const selected_id = this.selected.map(val => val.id)
			        //axios.post('/api/stores/delete', {'stores': this.selected})
			        axios.post('/api/stores/delete', {'stores': selected_id})
			        .then(res => {
		        		this.text = "Records Deleted Successfully!";
			        	this.selected.map(val => {
			        		const index = this.stores.data.indexOf(val)
							this.stores.data.splice(index, 1)
			        	})
			        	this.snackbar = true;
			        }).catch(err => {
			        	console.log(err.response)
		        		this.text = "Error Deleting Records!";
		        		this.snackbar = true;
			        })
		        }
	    	},
	    	searchIt(e) {
	    		if(e.length > 2) {
	    			axios.get(`/api/stores/${e}`)
	    			.then(res => this.stores = res.data.stores)
	    			.catch(err => console.dir(err.response))
	    		}
	    		if(e.length<=0){
		          	axios.get(`/api/stores`)
		            .then(res => this.stores = res.data.stores)
		            .catch(err => console.dir(err.response))
		        }
	    	},
		    paginate(e) {
		    	const sortBy = e.sortBy.length == 0 ? "name" : e.sortBy[0];
		    	const orderBy = e.sortDesc.length > 0 && e.sortDesc[0] ? "asc" : "desc";
		    	//const sortBy = this.options.sortBy.length == 0 ? "name" : this.options.sortBy[0];
		    	//const orderBy = this.options.sortDesc.length > 0 && this.options.sortDesc[0] ? "asc" : "desc";
				axios.get(`/api/stores`, {params: {'page': e.page,'per_page': e.itemsPerPage, 'sort_by': sortBy, 'order_by': orderBy}})
				.then(res => {
					this.stores = res.data.stores
         			this.categories = res.data.categories;
				})
				.catch(err => {
					if(err.response.status == 401) {
						localStorage.removeItem('token');
						this.$router.push('/login');
					}
				})
		   	},
		    initialize () {
			    axios.interceptors.request.use((config) => {
                    this.loading = true; 
                    return config;
                }, (error) => {
                    this.loading = false;
                    return Promise.reject(error);
                });

                axios.interceptors.response.use((response) => {
                    this.loading = false;
                    return response;
                }, (error) => {
                    this.loading = false;
                    return Promise.reject(error);
                });
			},
		    editItem (item) {
		        this.editedIndex = this.stores.data.indexOf(item)
		        this.editedItem = Object.assign({}, item)
		        this.dialog = true
		    },
		    deleteItem (item) {
		        const index = this.stores.data.indexOf(item)
		        let decide = confirm('Are you sure you want to delete this item?')
		        if(decide) {
			        axios.delete('/api/stores/' + item.id)
			        .then(res => {
		        		this.text = "Record Deleted Successfully!";
			        	this.snackbar = true;
			        	this.stores.data.splice(index, 1)
			        }).catch(err => {
			        	console.log(err.response)
		        		this.text = "Error Deleting Record!";
		        		this.snackbar = true;
			        })
		        }
		    },
		    close () {
		        this.dialog = false
		        setTimeout(() => {
			        this.editedItem = Object.assign({}, this.defaultItem)
			        this.editedIndex = -1
		        }, 300)
		    }, 
		    save () {
		        if (this.editedIndex > -1) {
		        	const index = this.editedIndex
		        	axios.put('/api/stores/' + this.editedItem.id, this.editedItem)
		        	.then(res => {
		        		this.text = "Record Updated Successfully!";
		        		this.snackbar = true;
		        		Object.assign(this.stores.data[index], res.data.store)
		        	})
		        	.catch(err => {
		        		console.log(err.response)
		        		this.text = "Error Updating Record!";
		        		this.snackbar = true;
		        	})
		        } else {
			        axios.post('/api/stores', this.editedItem)
			    	.then(res => {
		        		this.text = "Record Added Successfully!";
		        		this.snackbar = true;
			    		this.stores.data.push(res.data.store)
			    	})
			    	.catch(err => {
		        		console.log(err.response)
		        		this.text = "Error Inserting Record!";
		        		this.snackbar = true;
			    	})
		        }
		        this.close()
		    },
	    },
	}
</script>
<style scoped></style>