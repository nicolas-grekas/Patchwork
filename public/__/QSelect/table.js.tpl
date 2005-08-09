QSelect(lE,(function(QE)
{
	QE=new QSelectSearch([0<!-- LOOP $DATA -->,{$VALUE|escape:'js'}<!-- END:LOOP -->])
	return function(V,C,R)
	{
		R=[]
		if(V&&QE)QE.search(V,function(i){R[R.length]=i})
		C(R)
	}
})(),1)
