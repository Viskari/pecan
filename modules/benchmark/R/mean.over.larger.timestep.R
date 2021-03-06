##' @name mean.over.larger.timestep 
##' @title Calculate benchmarking statistics
##' @param date.fine numeric
##' @param data.fine data.frame
##' @param date.coarse numeric
##' @export mean.over.larger.timestep
##' 
##' @author Betsy Cowdery, Michael Dietze
mean.over.larger.timestep <- function(date.coarse, date.fine, data.fine){
  tapply(X=data.fine, INDEX=findInterval(date.fine, date.coarse), FUN=mean)
}
