pack:
	cd core/app/frontend/app && npm run build
	-rm -f rulehook.zip && git archive --prefix=rulehook/ -o rulehook.zip HEAD;
build:
	cd core/app/frontend/app && npm run build
