# Vuex Storage

Из-за использования единого дерева состояния, все глобальные данные приложения оказываются помещены в один большой объект. По мере роста приложения, хранилище может существенно раздуться.
To use the unified state tree, all the app's global data are placed into one big object. During the app's growth, the storage might be increased significantly.

To help managing the storage, Vuex lets you separate the storage into modules. Every module can contain its own state, mutations, actions, getters and the included submpdules.

More info on Vuex modules can be found here: <https://vuex.vuejs.org/ru/guide/modules.html>.

## Module registration

```javascript
context.registerVuexModule({
	state: {
		//...
	},
	mutations: {
		//...
	},
	getters: {
		//...
	},
	actions: {
		//...
	}
});
```

Module's state will be available as `store.state.myModule`, where `myModule` is your module's name.

## Module initialization

To initialize the module when launching the app, you can use the `init` method.

```javascript
context.registerVuexModule({
	actions: {
		init(context) {
			//...
		}
	}
});
```

?>`init` method runs automatically in the according Vuex module's context when the app's loading.
