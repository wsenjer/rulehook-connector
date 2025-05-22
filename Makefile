pack:
	cd core/app/frontend/app && npm run build
	-rm -f woocommerce-rulehook-connector.zip && git archive --prefix=rulehook-connector/ -o rulehook-connector.zip HEAD;
build:
	cd core/app/frontend/app && npm run build
