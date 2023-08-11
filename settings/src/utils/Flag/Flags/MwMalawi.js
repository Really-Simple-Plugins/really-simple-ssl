import * as React from "react";
const SvgMwMalawi = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="MW_-_Malawi_svg__a"
      width={16}
      height={12}
      x={0}
      y={0}
      maskUnits="userSpaceOnUse"
      style={{
        maskType: "luminance",
      }}
    >
      <path fill="#fff" d="M0 0h16v12H0z" />
    </mask>
    <g mask="url(#MW_-_Malawi_svg__a)">
      <path
        fill="#E11C1B"
        fillRule="evenodd"
        d="M0 0v12h16V0H0Z"
        clipRule="evenodd"
      />
      <mask
        id="MW_-_Malawi_svg__b"
        width={16}
        height={12}
        x={0}
        y={0}
        maskUnits="userSpaceOnUse"
        style={{
          maskType: "luminance",
        }}
      >
        <path
          fill="#fff"
          fillRule="evenodd"
          d="M0 0v12h16V0H0Z"
          clipRule="evenodd"
        />
      </mask>
      <g fillRule="evenodd" clipRule="evenodd" mask="url(#MW_-_Malawi_svg__b)">
        <path fill="#272727" d="M0 0v5h16V0H0Z" />
        <path fill="#5EAA22" d="M0 8v4h16V8H0Z" />
        <path
          fill="#E11C1B"
          d="M2 6.973v-.001h-.004.005Zm.315-1.91h.051l-.078-.012.027.012Zm1.464 0c.026-.05.053-.1.082-.149l-1.145-.758 1.35.44c.076-.106.157-.208.243-.307l-.992-.945 1.264.66c.093-.089.19-.174.291-.255L4.07 2.641l1.133.867a4.78 4.78 0 0 1 .327-.2l-.578-1.24.957 1.051c.114-.05.231-.098.35-.14l-.325-1.333.741 1.202c.12-.032.24-.06.364-.083l-.054-1.378.493 1.312a5.46 5.46 0 0 1 .37-.027L8.07 1.3l.224 1.372c.124.005.247.015.369.028l.493-1.313-.054 1.38c.122.023.243.051.362.083l.743-1.204-.326 1.336c.118.042.235.09.348.14l.96-1.054-.58 1.245c.111.062.22.128.324.198l1.138-.87-.806 1.113c.1.08.197.165.288.253l1.271-.663-.997.95c.085.098.165.2.24.304l1.358-.442-1.152.763.08.144h-.983c-.677-.938-1.915-1.566-3.33-1.566-1.414 0-2.651.628-3.328 1.566h-.933Zm1.5 0c.642-.661 1.65-1.087 2.782-1.087 1.132 0 2.14.426 2.781 1.087H5.28Zm8.495 0h.052l.027-.012-.079.012Zm.367 1.909h.004-.004Z"
        />
      </g>
    </g>
  </svg>
);
export default SvgMwMalawi;
