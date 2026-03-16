import {
	ModuleFlatObjectNamed,
	ModuleFlatObjects,
	type EditPost,
} from '@divi/types';

export type ModuleFlatObjectItems =
	ModuleFlatObjectNamed<'smartvideo/smartvideo'>;

export type SmartVideoModuleFlatObjects =
	ModuleFlatObjects<ModuleFlatObjectItems>;

export type SmartVideoMutableEditPostStoreState =
	EditPost.Store.State<SmartVideoModuleFlatObjects>;

export type SmartVideoEditPostStoreState =
	EditPost.Store.ImmutableState<SmartVideoModuleFlatObjects>;
