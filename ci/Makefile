.PHONY: info deploy backup restore list-backups deploy-staging deploy-production backup-production copy-production-to-staging promote-staging-to-production

info:
	@echo "Makefile for CI/CD tasks for interledger.org website"
	@echo ""
	@echo "Available tasks:"
	@echo "  deploy ENV=<environment> RUN=<run-identifier>       Build and deploy the staging version of the site to Cloud Run"
	@echo "  backup ENV=<environment> RUN=<run-identifier>       Backup the staging database and files"
	@echo "  restore ENV=<environment> FROM-RUN=<run-identifier> TARGETENV=<target-environment>    Restore the staging database and files from a specific backup"
	@echo "  list-backups ENV=<environment>                      List available backups for the specified environment"


deploy:
ifndef ENV
	$(error Error: ENV variable is not set. Usage: make deploy ENV=<environment> RUN=<run-identifier>)
endif
ifndef RUN
	$(eval RUN=$(shell hostname)-$(shell date +%Y%m%d))
endif
	@echo "Deploying to $(ENV) environment with run identifier: $(RUN)"
ifeq ($(ENV),staging)
	$(eval TARGETENV=stage)
	$(eval SERVICE_NAME=interledger-org-staging)
else ifeq ($(ENV),production)
	$(eval TARGETENV=prod)
	$(eval SERVICE_NAME=interledger-org)
else
	$(error Error: ENV variable must be either 'staging' or 'production')
endif
	cd .. && docker build --target $(TARGETENV) -t us-east1-docker.pkg.dev/interledger-websites/interledger-org/website:$(ENV)-$(RUN) -f docker/Dockerfile .
	docker push us-east1-docker.pkg.dev/interledger-websites/interledger-org/website:$(ENV)-$(RUN)
	gcloud run deploy $(SERVICE_NAME) --image=us-east1-docker.pkg.dev/interledger-websites/interledger-org/website:$(ENV)-$(RUN) --region=us-east1 --platform=managed
	

backup:
	@echo "Backing up $(ENV) database and files for run $(RUN)..."
	@echo "TODO"

restore:
	@echo "Restoring $(TARGETENV) database and files from backup of run $(FROM-RUN)..."
	@echo "TODO"

list-backups:
	@echo "Listing available backups for $(ENV)..."
	@echo "TODO"
