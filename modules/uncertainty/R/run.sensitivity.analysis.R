#--------------------------------------------------------------------------------------------------#
##' run sensitivity.analysis
##' 
##' @name run.sensitivity.analysis
##' @title run sensitivity.analysis
##' @return nothing, saves \code{sensitivity.results} as sensitivity.results.Rdata,
##' sensitivity plots as sensitivityanalysis.pdf, and variance decomposition 'popsicle plot'
##' as variancedecomposition.pdf a side effect.
##' @export
##' @author David LeBauer
##'
run.sensitivity.analysis <- function(){
  if(!exists("settings")){ # temporary hack
                        # waiting on http://stackoverflow.com/q/11005478/199217
    settings <- list(outdir = "/tmp/",
                     pfts = list(pft = list(name = "ebifarm.pavi",
                                   outdir = "/tmp/")),
                     sensitivity.analysis = NULL)
  }
  
  ### !!! This with below seems repetitive.  Should only need one if sa.analysis check. SPS
  if ('sensitivity.analysis' %in% names(settings)) {
    
    ### Load parsed model results
    load(paste(settings$outdir, 'output.Rdata', sep=''))
    load(paste(settings$outdir, 'samples.Rdata', sep=''))
    
    ### Generate SA output and diagnostic plots
    sensitivity.results <- list()
    for(pft in settings$pfts){
      print(pft$name)
      if('sensitivity.analysis' %in% names(settings)) {
        traits <- names(trait.samples[[pft$name]])
        quantiles.str <- rownames(sa.samples[[pft$name]])
        quantiles.str <- quantiles.str[which(quantiles.str != '50')]
        quantiles <- as.numeric(quantiles.str)/100
        ## ensemble.output <- read.ensemble.output(settings$ensemble$size, settings$outdir, pft.name=pft$name)

        ## only perform sensitivity analysis on traits where no more than 2 results are missing
        good.saruns <- sapply(sensitivity.output[[pft$name]], function(x) sum(is.na(x)) <=2)
        if(!all(good.saruns)) { # if any bad saruns, reduce list of traits and print warning
          bad.saruns <- !good.saruns
          warning(paste('missing >2 runs for', vecpaste(traits[bad.saruns]),
                        '\n sensitivity analysis or variance decomposition will be performed on these trait(s)',
                        '\n it is likely that the runs did not complete, this should be fixed !!!!!!'))
        }
        
        ### Gather SA results
        sensitivity.results[[pft$name]] <- sensitivity.analysis(trait.samples = trait.samples[[pft$name]][traits],
                                                                sa.samples = sa.samples[[pft$name]][ ,traits],
                                                                sa.output = sensitivity.output[[pft$name]][ ,traits],
                                                                outdir = pft$outdir)
        
        ### Send diagnostic output to the console
        print(sensitivity.results[[pft$name]]$variance.decomposition.output)
        print(sensitivity.output[[pft$name]])
        
        ### Generate SA diagnostic plots
        sensitivity.plots <- plot.sensitivities(sensitivity.results[[pft$name]]$sensitivity.output,
                                                linesize = 1,
                                                dotsize = 3)
        pdf(paste(pft$outdir, 'sensitivityanalysis.pdf', sep = ''), height = 12, width = 9)
        print(sensitivity.plots)
        dev.off()

        ### Generate VD diagnostic plots
        vd.plots <- plot.variance.decomposition(sensitivity.results[[pft$name]]$variance.decomposition.output)
                                        #variance.scale = log, variance.prefix='Log')
        pdf(paste(pft$outdir, 'variancedecomposition.pdf', sep=''), width = 11, height = 8)
        do.call(grid.arrange, c(vd.plots, ncol = 4))
        dev.off()  
      }
    }  ## end if sensitivity analysis

    save(sensitivity.results,
         file = paste(settings$outdir,
           "sensitivity.results.Rdata", sep = ""))
  }
}
#==================================================================================================#


####################################################################################################
### EOF.  End of R script file.        			
####################################################################################################