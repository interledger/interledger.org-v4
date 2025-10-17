.PHONY: stage

stage:
	cd .. && docker build --target stage -t us-east1-docker.pkg.dev/interledger-websites/interledger-org/website:latest -f docker/Dockerfile .
	docker push us-east1-docker.pkg.dev/interledger-websites/interledger-org/website
	gcloud run deploy interledger-org-staging --image=us-east1-docker.pkg.dev/interledger-websites/interledger-org/website:latest --region=us-east1 --platform=managed