import * as React from "react";
const SvgNpNepal = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <path fill="#fff" d="M0 0h16v12H0z" />
    <path
      fill="#C51918"
      stroke="#4857A1"
      d="m5.616 6.32 4.316 5.18H.5V.842L9.04 5.5H4.931l.684.82Z"
    />
    <mask
      id="NP_-_Nepal_svg__a"
      width={11}
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
        stroke="#fff"
        d="m5.616 6.32 4.316 5.18H.5V.842L9.04 5.5H4.931l.684.82Z"
      />
    </mask>
    <g fillRule="evenodd" clipRule="evenodd" mask="url(#NP_-_Nepal_svg__a)">
      <path
        fill="#F7FCFF"
        d="m2.915 10.005-.613.795-.028-1.003-.963.283.567-.828-.946-.337.946-.337-.567-.828.963.283.028-1.004.613.796.612-.796.029 1.004.963-.283-.567.828.945.337-.945.337.567.828-.963-.283-.029 1.003-.612-.795ZM2.9 4.07l-.32.415-.015-.524-.503.148.296-.433-.493-.176.493-.176-.296-.433.503.148.015-.524.32.416.32-.416.015.524.503-.148-.296.433.493.176-.493.176.296.433-.503-.148-.015.524-.32-.416Z"
      />
      <path
        fill="#F9FAFA"
        d="M2.833 3.947c1.113.004 1.707-.627 1.707-.627.117.72-.79 1.2-1.696 1.2-.907 0-1.456-.654-1.794-1.2 0 0 .67.623 1.783.627Z"
      />
    </g>
  </svg>
);
export default SvgNpNepal;
