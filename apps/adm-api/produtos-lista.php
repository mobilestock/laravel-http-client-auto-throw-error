<?php require_once __DIR__ . '/cabecalho.php'; ?>
<?php acessoUsuarioVendedor(); ?>

<head>
    <link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, minimal-ui">
    <meta charset="UTF-8">
</head>

<style>
	input[type=checkbox] {
		transform: scale(2);
	}
	.foto {
		display: grid;
		grid-template-columns: 1fr 1fr 1fr 1fr 1fr;
		gap: 0.5rem;
	}
</style>

<v-app id="produtosListaVUE">
	<div class="container-fluid">
		<h2>Produtos</h2>
		<h3>Buscar:</h3>
		<!-- Filtros -->
		<div class="row">
			<div class="col-1">
				<label>ID</label>
				<div class="w-100">
					<input v-model="filtros.codigo" class="w-100 bg-light p-2 border rounded" @keydown.enter="carregarProdutos"/>
				</div>
			</div>
			<div class="col-2">
				<label>Descrição</label>
				<div class="w-100">
					<input v-model="filtros.descricao" class="w-100 bg-light p-2 border rounded" @keydown.enter="carregarProdutos"/>
				</div>
			</div>
			<div class="col-2">
				<label>Categoria</label>
				<div class="d-flex justify-center">
					<select v-model="filtros.categoria" class="w-100 bg-light p-2 border rounded" :disabled="!categorias?.length">
						<option v-for="categoria in categorias" :value="categoria.id">
							{{ categoria.nome }}
						</option>
					</select>
				</div>
			</div>
			<div class="col">
				<label>Fornecedor</label>
				<div class="d-flex justify-center">
					<select v-model="filtros.fornecedor" class="w-100 bg-light p-2 border rounded" :disabled="!fornecedores?.length">
						<option v-for="fornecedor in fornecedores" :value="fornecedor.id">
							{{ fornecedor.nome }}
						</option>
					</select>
				</div>
			</div>
            <div class="col-auto">
                <label>Tag</label>
                <div class="d-flex justify-center">
                    <select v-model="filtros.tag" class="w-100 bg-light p-2 border rounded">
                        <option value="">Todas</option>
                        <option value="TRADICIONAL">Tradicional</option>
                        <option value="MODA">Moda</option>
                    </select>
                </div>
            </div>
			<div class="col-auto">
				<label class="w-100 text-center">Não Avaliados</label>
				<div class="d-flex justify-center py-3">
					<input v-model="filtros.nao_avaliado" type="checkbox" />
				</div>
			</div>
			<div class="col-auto">
				<label class="w-100 text-center">Bloqueados</label>
				<div class="d-flex justify-center py-3">
					<input v-model="filtros.bloqueados" type="checkbox" />
				</div>
			</div>
			<div class="col-auto">
				<label class="w-100 text-center">Sem Fotos/Pub</label>
				<div class="d-flex justify-center py-3">
					<input v-model="filtros.sem_foto_pub" type="checkbox" />
				</div>
			</div>
			<div class="col-auto">
				<label>Fotos</label>
				<div class="d-flex justify-center">
					<select
						class="w-100 bg-light p-2 border rounded"
						:disabled="filtros.sem_foto_pub"
						v-model="filtros.fotos"
					>
						<option>0</option>
						<option>1</option>
						<option>2</option>
						<option>3</option>
						<option>4</option>
						<option>5</option>
					</select>
				</div>
			</div>
			<div class="col">
				<label class="text-white">.</label>
				<div class="w-100">
					<v-btn
						block
						color="success"
						:loading="carregando"
						@click="carregarProdutos"
					>Filtrar</v-btn>
				</div>
			</div>
			<div class="col">
				<label class="text-white">.</label>
				<div class="w-100">
					<v-btn
						block
						color="error"
						:loading="carregando"
						@click="limparFiltros"
					>Limpar</v-btn>
				</div>
			</div>
		</div>

		<br />

		<!-- Grade -->
		<v-data-table
			hide-default-footer
			:headers="cabecalho"
			:items="itens"
			:items-per-page="-1"
			:loading="carregando"
			class="elevation-1"
		>
			<template v-slot:item.fotos="{ item }" >
				<div class="foto">
					<v-img v-for="(foto, index) in item.fotos" :key="index" width="6rem" height="6rem" :src="foto" >
				</div>
			</template>
			<template v-slot:item.tem_foto_pub="{ item }">
				<v-tooltip top>
					<template v-slot:activator="{ on, attrs }">
						<div
							class="text-center"
							v-bind="attrs"
							v-on="on"
						>
							<v-icon v-if="item.tem_foto_pub" color="green">mdi-check-circle</v-icon>
							<v-icon v-else color="red">mdi-camera</v-icon>
						</div>
					</template>
					<span>{{ item.mensagem }}</span>
				</v-tooltip>
			</template>

            <template v-slot:item.tag="{ item }">
                <v-btn
                    :color="item.tag === 'TRADICIONAL' ? 'blue' : 'pink'"
                    @click="atualizaTag(item)"
                    dark
                >
                    {{ item.tag }}
                </v-btn>
            </template>

			<template v-slot:item.editar="{ item }">
				<a
					:href="'fornecedores-produtos.php?id=' + item.id"
					target="_blank"
					rel="noopener noreferrer"
				>
					<v-icon @click="abrirProduto(item.id)" color="orange">
						fas fa-edit
					</v-icon>
				</a>
			</template>
		</v-data-table>
		<br />
		<div class="d-flex justify-content-around pb-4">
			<v-btn
				dense
				:dark="filtros.pagina > 1"
				:disabled="filtros.pagina <= 1"
				:loading="carregando"
				@click="filtros.pagina--"
			>
				<v-icon>mdi-chevron-left</v-icon>
				Produtos anteriores
			</v-btn>
			<v-chip dark>{{ filtros.pagina }}</v-chip>
			<v-btn
				dense
				:dark="itens.length >= 150"
				:disabled="itens.length < 150"
				:loading="carregando"
				@click="filtros.pagina++"
			>
				Proximos produtos
				<v-icon>mdi-chevron-right</v-icon>
			</v-btn>
		</div>
	</div>
	<v-snackbar v-model="snackBar.mostrar">{{ snackBar.mensagem }}</v-snackbar>
</v-app>

<script src="https://cdn.jsdelivr.net/npm/vue@2.x/dist/vue.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.js"></script>
<script src="js/MobileStockApi.js"></script>
<script type="module" src="js/produtos-lista.js"></script>
